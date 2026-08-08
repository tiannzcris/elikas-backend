"""
E-LIKAS SARIMA forecasting microservice.

A small, standalone FastAPI process -- deliberately NOT part of the Laravel
app's own process/language. Laravel (app/Services/SarimaForecastService.php)
calls this over HTTP with a plain JSON time series; this service has no
direct database access and no knowledge of the rest of the E-LIKAS schema,
by design -- it only knows how to fit a SARIMAX model to a list of
(date, value) points and return a forecast. Keeping it stateless like this
avoids duplicating DB credentials/coupling into a second language.

Run locally for development:
    uvicorn app:app --reload --port 8090

See README.md for venv setup, systemd deployment, and curl smoke tests.
"""

import os
from datetime import datetime, timedelta
from typing import Literal, Optional

import pandas as pd
from fastapi import FastAPI, Header, HTTPException
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from starlette.exceptions import HTTPException as StarletteHTTPException
from statsmodels.tsa.statespace.sarimax import SARIMAX

app = FastAPI(title="E-LIKAS SARIMA Forecasting Service")


@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(request, exc: StarletteHTTPException):
    """
    FastAPI's default error body is {"detail": <whatever was raised>}. Every
    HTTPException in this file is raised with a structured dict detail
    ({success, reason, message}) that matches the flat contract documented
    for SarimaForecastService.php on the Laravel side -- without this
    handler, that dict would get wrapped one level deeper than Laravel
    expects, and $response->json()['success'] would silently be missing.
    """
    if isinstance(exc.detail, dict):
        return JSONResponse(status_code=exc.status_code, content=exc.detail)

    return JSONResponse(
        status_code=exc.status_code,
        content={"success": False, "reason": "error", "message": str(exc.detail)},
    )


SERVICE_TOKEN = os.environ.get("SARIMA_SERVICE_TOKEN", "")

# Small-dataset pragmatism, matching the same honesty ethic as the Laravel
# side's PredictiveAnalyticsService (MIN_EVENTS_TO_TRAIN = 2, etc.): a
# capstone-scale system will not have years of weekly data, so this never
# assumes yearly seasonality (period 52) -- only weekly (7, for daily data)
# or ~monthly (4, for weekly data) unless the caller explicitly overrides it.
DEFAULT_SEASONAL_PERIODS = 7

# A gap of more than this many consecutive missing periods is NOT
# interpolated -- filling a long stretch of genuinely missing data would
# manufacture readings that were never actually recorded, which conflicts
# with this whole project's "never fabricate" ethic. A short gap (a station
# offline for a day or two) is a reasonable, clearly-documented exception.
MAX_INTERPOLATION_GAP = 3


class SeriesPoint(BaseModel):
    date: str
    value: Optional[float] = None


class ForecastRequest(BaseModel):
    metric: str
    series: list[SeriesPoint]
    horizon: int = Field(gt=0, le=60)
    days_per_period: int = Field(gt=0)
    seasonal_periods: Optional[int] = None


class ForecastPoint(BaseModel):
    date: str
    predicted: float
    lower_ci: float
    upper_ci: float


class Diagnostics(BaseModel):
    aic: float
    n_observations: int
    order: list[int]
    seasonal_order: list[int]


class ForecastResponse(BaseModel):
    success: Literal[True] = True
    model: str
    forecast: list[ForecastPoint]
    diagnostics: Diagnostics


class ErrorResponse(BaseModel):
    success: Literal[False] = False
    reason: str
    message: str


def verify_token(x_service_token: Optional[str]) -> None:
    if not SERVICE_TOKEN:
        # No token configured on this deployment -- fail closed, not open.
        # An operator who forgot to set SARIMA_SERVICE_TOKEN should get a
        # loud 401 on every request, not an accidentally-unauthenticated
        # service.
        raise HTTPException(status_code=401, detail="Service token not configured on the server.")
    if x_service_token != SERVICE_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid or missing X-Service-Token header.")


@app.get("/health")
def health():
    return {"status": "ok"}


def build_series(points: list[SeriesPoint], days_per_period: int) -> pd.Series:
    """
    Reindexes the given points onto a full, evenly-spaced date range at the
    given cadence, so statsmodels gets a proper DatetimeIndex with no silent
    date gaps. Short gaps (<= MAX_INTERPOLATION_GAP consecutive missing
    periods) are linearly interpolated; longer gaps are left as NaN and
    dropped before fitting, since manufacturing that much missing data would
    misrepresent it as observed.
    """
    parsed = []
    for p in points:
        try:
            d = datetime.strptime(p.date, "%Y-%m-%d")
        except ValueError:
            raise HTTPException(
                status_code=422,
                detail={"success": False, "reason": "invalid_input", "message": f"Invalid date '{p.date}', expected YYYY-MM-DD."},
            )
        parsed.append((d, p.value))

    parsed.sort(key=lambda x: x[0])
    series = pd.Series({d: v for d, v in parsed})

    full_index = pd.date_range(start=series.index.min(), end=series.index.max(), freq=f"{days_per_period}D")
    series = series.reindex(full_index)

    series = series.interpolate(method="linear", limit=MAX_INTERPOLATION_GAP, limit_area="inside")

    return series.dropna()


@app.post("/forecast", response_model=ForecastResponse, responses={422: {"model": ErrorResponse}, 500: {"model": ErrorResponse}})
def forecast(req: ForecastRequest, x_service_token: Optional[str] = Header(default=None)):
    verify_token(x_service_token)

    seasonal_periods = req.seasonal_periods or DEFAULT_SEASONAL_PERIODS

    series = build_series(req.series, req.days_per_period)

    min_required = max(10, 2 * seasonal_periods)
    if len(series) < min_required:
        raise HTTPException(
            status_code=422,
            detail={
                "success": False,
                "reason": "insufficient_data",
                "message": f"Need at least {min_required} observations to fit a seasonal model with period {seasonal_periods} "
                           f"(need >= 2x seasonal period), have {len(series)}.",
            },
        )

    try:
        model = SARIMAX(
            series,
            order=(1, 1, 1),
            seasonal_order=(1, 1, 1, seasonal_periods),
            enforce_stationarity=False,
            enforce_invertibility=False,
        )
        fitted = model.fit(disp=False)

        forecast_result = fitted.get_forecast(steps=req.horizon)
        predicted_mean = forecast_result.predicted_mean
        conf_int = forecast_result.conf_int(alpha=0.05)
    except Exception as e:  # noqa: BLE001 -- statsmodels can raise many distinct internal error types
        raise HTTPException(
            status_code=500,
            detail={"success": False, "reason": "fit_failed", "message": f"SARIMA fitting failed: {e}"},
        )

    last_date = series.index.max()
    step = timedelta(days=req.days_per_period)

    forecast_points = []
    for i in range(req.horizon):
        point_date = last_date + step * (i + 1)
        predicted = float(predicted_mean.iloc[i])
        lower = float(conf_int.iloc[i, 0])
        upper = float(conf_int.iloc[i, 1])

        # Rainfall/wind speed can't be negative -- clamp rather than show a
        # confusing negative forecast the confidence interval math can
        # technically produce for a low/near-zero predicted value.
        forecast_points.append(ForecastPoint(
            date=point_date.strftime("%Y-%m-%d"),
            predicted=round(max(0.0, predicted), 2),
            lower_ci=round(max(0.0, lower), 2),
            upper_ci=round(max(0.0, upper), 2),
        ))

    return ForecastResponse(
        model=f"SARIMAX(1,1,1)x(1,1,1,{seasonal_periods})",
        forecast=forecast_points,
        diagnostics=Diagnostics(
            aic=round(float(fitted.aic), 2),
            n_observations=len(series),
            order=[1, 1, 1],
            seasonal_order=[1, 1, 1, seasonal_periods],
        ),
    )

# E-LIKAS SARIMA Forecasting Service

A small, standalone Python/FastAPI process that fits SARIMA/SARIMAX models
to weather time series and returns a forecast. **Not part of the Laravel
app** — it runs as its own process on the VPS, and Laravel
(`app/Services/SarimaForecastService.php`) talks to it over plain HTTP.

This service has no database access of its own. Laravel pulls the readings
from `weather_readings` and sends them in the request body — this service
only knows how to fit a model to a list of `(date, value)` points.

## 1. Local setup (on the VPS)

Requires Python 3.9+ (uses the built-in `list[...]` generic syntax).

```bash
cd sarima-service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

Set a real shared-secret token (this must match `SARIMA_SERVICE_TOKEN` in
the Laravel app's `.env` **exactly** — the service fails closed and refuses
every request if either side leaves it blank):

```bash
export SARIMA_SERVICE_TOKEN="pick-a-long-random-string-here"
```

Run it for a quick local check:

```bash
uvicorn app:app --host 127.0.0.1 --port 8090
```

## 2. Smoke-test with curl

Health check (no auth needed):

```bash
curl http://127.0.0.1:8090/health
# {"status":"ok"}
```

Forecast (replace the token, and note this tiny 12-point example is only
enough to prove the endpoint responds — it's below the real minimum-data
threshold and will correctly return a 422 `insufficient_data` error, which
is the expected/correct behavior, not a bug):

```bash
curl -X POST http://127.0.0.1:8090/forecast \
  -H "Content-Type: application/json" \
  -H "X-Service-Token: pick-a-long-random-string-here" \
  -d '{
    "metric": "rainfall_mm",
    "series": [
      {"date": "2026-01-01", "value": 10.2}, {"date": "2026-01-02", "value": 5.0},
      {"date": "2026-01-03", "value": 0.0}, {"date": "2026-01-04", "value": 12.1},
      {"date": "2026-01-05", "value": 8.4}, {"date": "2026-01-06", "value": 3.2},
      {"date": "2026-01-07", "value": 1.0}, {"date": "2026-01-08", "value": 9.9},
      {"date": "2026-01-09", "value": 6.6}, {"date": "2026-01-10", "value": 2.2},
      {"date": "2026-01-11", "value": 0.5}, {"date": "2026-01-12", "value": 11.0}
    ],
    "horizon": 7,
    "days_per_period": 1,
    "seasonal_periods": null
  }'
```

To actually see a successful forecast, use the real sample fixture instead
— import it via Laravel first (`php artisan weather:import
database/samples/pagasa_sample_daily.csv --sample`), then trigger a
forecast from the web UI or `POST /api/v1/weather-forecasts` once
`SARIMA_SERVICE_URL` is set (step 4 below). Laravel builds the `series`
array from the database automatically; you don't need to hand-write a
120-point curl body yourself.

## 3. Deploy as a systemd service (so it survives reboots/crashes)

```bash
sudo useradd -r -s /usr/sbin/nologin elikas   # if not already created
sudo mkdir -p /opt/elikas-sarima
sudo cp -r sarima-service/* /opt/elikas-sarima/
cd /opt/elikas-sarima
sudo python3 -m venv venv
sudo ./venv/bin/pip install -r requirements.txt
sudo chown -R elikas:elikas /opt/elikas-sarima

sudo cp elikas-sarima.service /etc/systemd/system/
sudo nano /etc/systemd/system/elikas-sarima.service   # replace the token placeholder
sudo systemctl daemon-reload
sudo systemctl enable --now elikas-sarima
sudo systemctl status elikas-sarima
```

Bound to `127.0.0.1` deliberately — reachable only from the same VPS where
Laravel also runs, not exposed to the public internet directly. If Laravel
and Python ever end up on different hosts, add a reverse-proxy/firewall
rule rather than changing this to bind on `0.0.0.0`.

## 4. Connect Laravel to it

In the Laravel app's `.env`:

```
SARIMA_SERVICE_URL=http://127.0.0.1:8090
SARIMA_SERVICE_TOKEN=pick-a-long-random-string-here
```

Must match the token set in step 1/3 exactly. Once both are set, the
"Rainfall/wind forecast (SARIMA)" card on the Predictive Analytics page
will actually be able to generate a real forecast instead of returning the
`not_configured` degrade-gracefully state.

## API contract

`GET /health` → `{"status": "ok"}`

`POST /forecast` (requires `X-Service-Token` header):

Request:
```json
{
  "metric": "rainfall_mm",
  "series": [{"date": "2026-01-01", "value": 12.4}],
  "horizon": 14,
  "days_per_period": 1,
  "seasonal_periods": null
}
```

Success (200):
```json
{
  "success": true,
  "model": "SARIMAX(1,1,1)x(1,1,1,7)",
  "forecast": [{"date": "2026-08-09", "predicted": 8.3, "lower_ci": 2.1, "upper_ci": 14.5}],
  "diagnostics": {"aic": 812.4, "n_observations": 90, "order": [1,1,1], "seasonal_order": [1,1,1,7]}
}
```

Error (422 `insufficient_data`/`invalid_input`, or 500 `fit_failed`):
```json
{"success": false, "reason": "insufficient_data", "message": "..."}
```

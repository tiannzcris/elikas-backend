<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\PredictRequest;
use App\Http\Resources\AnalyticsPredictionResource;
use App\Models\AnalyticsPrediction;
use App\Models\SystemLog;
use App\Services\PredictiveAnalyticsService;
use Illuminate\Http\Request;

class PredictiveAnalyticsController extends Controller
{
    /**
     * Shows the current state of the training data honestly -- how many
     * historical events are usable, whether that's enough to predict at
     * all, and current MAE/R² if there's enough for evaluation. The
     * frontend uses this to explain itself before anyone generates a
     * forecast, rather than surprise them with an error or an unqualified
     * number.
     */
    public function status(PredictiveAnalyticsService $service)
    {
        $service->refreshTrainingSnapshot();
        $data = $service->getTrainingDataset();
        $evaluation = $service->evaluate();

        return $this->success([
            'historical_event_count' => $data['event_count'],
            'minimum_to_predict' => 2,
            'minimum_to_evaluate' => 4,
            'can_predict' => $data['event_count'] >= 2,
            'evaluation' => $evaluation, // null until 4+ events exist
        ]);
    }

    public function index(Request $request)
    {
        $predictions = AnalyticsPrediction::query()
            ->with('evacuationEvent')
            ->latest('generated_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(AnalyticsPredictionResource::collection($predictions)->response()->getData(true));
    }

    public function store(PredictRequest $request, PredictiveAnalyticsService $service)
    {
        $validated = $request->validated();
        $service->refreshTrainingSnapshot();

        try {
            $prediction = $service->predict(
                $validated['rainfall_mm'],
                $validated['wind_speed_kph'],
                $validated['evacuation_event_id'] ?? null
            );
        } catch (\RuntimeException $e) {
            // Not enough historical data yet -- an expected, explainable
            // state for a new system, not a server error.
            return $this->error($e->getMessage(), 422);
        }

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'prediction.generated',
            'description' => "{$request->user()->name} generated a forecast: {$prediction->predicted_evacuees} predicted evacuees.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new AnalyticsPredictionResource($prediction->load('evacuationEvent')),
            'Forecast generated successfully.',
            201
        );
    }
}

<?php

namespace App\Services;

use App\Models\AnalyticsPrediction;
use App\Models\CashAssistanceDisbursement;
use App\Models\EvacuationEvent;
use App\Models\EvacuationRecord;
use App\Models\PredictionDataset;
use App\Models\ReliefDistribution;
use Phpml\Metric\Regression as RegressionMetrics;
use Phpml\Regression\LeastSquares;

/**
 * Forecasts expected evacuee volume for an upcoming/ongoing disaster event
 * from its environmental inputs (rainfall, wind speed), using PHP-ML's
 * LeastSquares linear regression -- trained on this system's OWN historical
 * disaster events, which accumulate automatically as real disasters get
 * registered and closed out through the normal workflow. No separate manual
 * "historical data entry" step is needed; every closed evacuation_event
 * IS a labeled training example the moment it's marked closed.
 *
 * ============================================================================
 * HONEST LIMITATIONS -- read before presenting this as more than it is
 * ============================================================================
 * 1. SMALL-DATA REALITY: a brand-new system has zero historical events. This
 *    service requires a minimum of 2 closed events (the mathematical floor
 *    for fitting a line) before it will predict anything at all, and won't
 *    report MAE/R² until at least 4 events exist (see MIN_EVENTS_FOR_EVALUATION)
 *    -- a single train/test split isn't meaningful with fewer than ~4-5
 *    samples, so this uses leave-one-out cross-validation instead, which is
 *    the more statistically appropriate technique for small datasets. This
 *    matches the limitation already stated in Chapter 1/3: forecast
 *    reliability is inherently bounded by how much real history exists.
 *
 * 2. ONE REGRESSION, TWO DERIVED ESTIMATES: only "predicted evacuees" is an
 *    actual trained regression (rainfall + wind speed -> persons displaced).
 *    "Predicted center occupancy" and "predicted resource cost" are NOT
 *    separate regression models -- they're the evacuee prediction multiplied
 *    by a simple historical average ratio (e.g. avg. cost per displaced
 *    person across past events). This is a deliberate simplification: with
 *    only a handful of historical events, a second or third regression model
 *    would have even less data to learn from and would not be more reliable
 *    than a simple ratio -- added model complexity here would not have
 *    added genuine accuracy.
 *
 * 3. WHEN NO HISTORY EXISTS FOR THE RATIOS ABOVE: a documented rough default
 *    (70% of evacuees sheltering inside a center; ~PHP500 resource cost per
 *    person) is used, and the returned prediction's input_payload explicitly
 *    flags 'used_default_ratios' => true so this is never silently mistaken
 *    for a data-backed estimate.
 * ============================================================================
 */
class PredictiveAnalyticsService
{
    private const MIN_EVENTS_TO_TRAIN = 2;

    private const MIN_EVENTS_FOR_EVALUATION = 4;

    private const DEFAULT_INSIDE_CENTER_RATIO = 0.7;

    private const DEFAULT_COST_PER_PERSON = 500.0;

    /**
     * Recomputes the prediction_datasets snapshot from every CLOSED event
     * that has rainfall/wind speed recorded. Safe to call repeatedly --
     * upserts rather than duplicating rows, so it can run before every
     * training/evaluation pass to pick up newly-closed events.
     */
    public function refreshTrainingSnapshot(): int
    {
        $closedEvents = EvacuationEvent::where('status', 'closed')
            ->whereNotNull('rainfall_mm')
            ->whereNotNull('max_wind_speed_kph')
            ->get();

        foreach ($closedEvents as $event) {
            $displacedEvacueeIds = EvacuationRecord::where('evacuation_event_id', $event->id)
                ->pluck('evacuee_id')->unique();
            $insideCenterEvacueeIds = EvacuationRecord::where('evacuation_event_id', $event->id)
                ->where('displacement_type', 'inside_center')
                ->pluck('evacuee_id')->unique();

            $actualPersons = $displacedEvacueeIds->count();
            $insideRatio = $actualPersons > 0 ? $insideCenterEvacueeIds->count() / $actualPersons : 0;

            $totalCost = ReliefDistribution::where('evacuation_event_id', $event->id)->sum('total_cost')
                + CashAssistanceDisbursement::where('evacuation_event_id', $event->id)->sum('total_cost');

            $this->upsertDataset($event->id, 'rainfall_mm', (float) $event->rainfall_mm);
            $this->upsertDataset($event->id, 'wind_speed_kph', (float) $event->max_wind_speed_kph);
            $this->upsertDataset($event->id, 'actual_persons_displaced', $actualPersons);
            $this->upsertDataset($event->id, 'actual_inside_center_ratio', round($insideRatio, 4));
            $this->upsertDataset($event->id, 'actual_resource_cost', (float) $totalCost);
        }

        return $closedEvents->count();
    }

    /**
     * Builds the feature matrix (samples) and target vector for training,
     * from whatever is currently in prediction_datasets. An event only
     * contributes a row if it has all three needed values -- an event
     * snapshotted before this system tracked one of these fields, for
     * instance, is safely excluded rather than crashing on a missing key.
     */
    public function getTrainingDataset(): array
    {
        $grouped = PredictionDataset::whereIn('parameter', ['rainfall_mm', 'wind_speed_kph', 'actual_persons_displaced'])
            ->get()
            ->groupBy('evacuation_event_id');

        $samples = [];
        $targets = [];
        $eventIds = [];

        foreach ($grouped as $eventId => $params) {
            $byParam = $params->keyBy('parameter');
            if ($byParam->has('rainfall_mm') && $byParam->has('wind_speed_kph') && $byParam->has('actual_persons_displaced')) {
                $samples[] = [(float) $byParam['rainfall_mm']->value, (float) $byParam['wind_speed_kph']->value];
                $targets[] = (float) $byParam['actual_persons_displaced']->value;
                $eventIds[] = $eventId;
            }
        }

        return ['samples' => $samples, 'targets' => $targets, 'event_ids' => $eventIds, 'event_count' => count($samples)];
    }

    /**
     * Leave-one-out cross-validation: trains N separate models, each
     * leaving exactly one historical event out as a held-out test case,
     * then measures error across all N held-out predictions. More
     * appropriate than a single train/test split when the whole dataset
     * might only be single digits in size, which is the realistic case for
     * a system this new.
     */
    public function evaluate(): ?array
    {
        $data = $this->getTrainingDataset();
        $n = $data['event_count'];

        if ($n < self::MIN_EVENTS_FOR_EVALUATION) {
            return null;
        }

        $actuals = [];
        $predictions = [];

        // Wrapped defensively: ordinary least squares fails with a
        // "matrix is singular" error when the training data's features
        // are perfectly (or near-perfectly) proportional to each other --
        // e.g. every historical event happening to have wind speed at
        // exactly half its rainfall. That's a realistic thing to hit with
        // a handful of hand-entered test events, and this is a passive
        // status check, not something the user directly triggered -- so
        // it should degrade to "can't show accuracy metrics right now"
        // rather than break the whole page.
        try {
            for ($i = 0; $i < $n; $i++) {
                $trainSamples = $data['samples'];
                $trainTargets = $data['targets'];
                $testSample = array_splice($trainSamples, $i, 1)[0];
                $testTarget = array_splice($trainTargets, $i, 1)[0];

                $model = new LeastSquares();
                $model->train($trainSamples, $trainTargets);

                $actuals[] = $testTarget;
                $predictions[] = $model->predict($testSample);
            }
        } catch (\Throwable $e) {
            return null;
        }

        // Per-event pairs for the historical accuracy chart -- same
        // held-out predictions just computed above, paired back up with
        // each event's name and sorted chronologically so the chart reads
        // left-to-right as an actual timeline, not an arbitrary order.
        $events = EvacuationEvent::whereIn('id', $data['event_ids'])->get()->keyBy('id');
        $perEvent = [];
        foreach ($data['event_ids'] as $i => $eventId) {
            $perEvent[] = [
                'event_name' => $events->get($eventId)?->name ?? "Event #{$eventId}",
                'start_date' => $events->get($eventId)?->start_date,
                'actual' => $actuals[$i],
                'predicted' => round($predictions[$i], 1),
            ];
        }
        usort($perEvent, fn ($a, $b) => ($a['start_date'] ?? '') <=> ($b['start_date'] ?? ''));

        return [
            'mae' => RegressionMetrics::meanAbsoluteError($predictions, $actuals),
            'r2' => $this->computeR2($actuals, $predictions),
            'sample_count' => $n,
            'per_event' => $perEvent,
        ];
    }

    /**
     * Trains on ALL available historical data (not a train/test split --
     * evaluate() above already tells us how reliable the approach is via
     * LOOCV; the actual deployed prediction should use every data point
     * available, not withhold some of an already-tiny dataset) and
     * forecasts for the given rainfall/wind speed inputs.
     */
    public function predict(float $rainfallMm, float $windSpeedKph, ?int $evacuationEventId = null): AnalyticsPrediction
    {
        $data = $this->getTrainingDataset();

        if ($data['event_count'] < self::MIN_EVENTS_TO_TRAIN) {
            throw new \RuntimeException(
                'Not enough historical data yet to generate a forecast. At least '.self::MIN_EVENTS_TO_TRAIN.
                ' completed disaster events with recorded outcomes are needed (currently have '.
                $data['event_count'].'). Close out more events as they conclude to build this up over time.'
            );
        }

        // Same numerical failure mode as evaluate()'s LOOCV loop can happen
        // here too -- but this is an action the user directly triggered,
        // so it deserves a specific, actionable message rather than a
        // silent fallback.
        try {
            $model = new LeastSquares();
            $model->train($data['samples'], $data['targets']);
            $predictedPersons = max(0, (int) round($model->predict([$rainfallMm, $windSpeedKph])));
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Could not train a model from the current historical data -- this usually means the '.
                'recorded events don\'t have enough variation between them yet (for example, every '.
                'event happening to have wind speed at exactly the same ratio to its rainfall). Add an '.
                'event with a different rainfall/wind-speed relationship and try again.'
            );
        }

        $evaluation = $this->evaluate(); // null until MIN_EVENTS_FOR_EVALUATION is reached

        $insideRatioRow = PredictionDataset::where('parameter', 'actual_inside_center_ratio')->pluck('value');
        $usedDefaults = $insideRatioRow->isEmpty();
        $insideRatio = $usedDefaults ? self::DEFAULT_INSIDE_CENTER_RATIO : (float) $insideRatioRow->avg();
        $costPerPerson = $this->historicalAverageCostPerPerson($usedDefaults);

        $predictedOccupancy = (int) round($predictedPersons * $insideRatio);
        $predictedResourceCost = round($predictedPersons * $costPerPerson, 2);

        return AnalyticsPrediction::create([
            'evacuation_event_id' => $evacuationEventId,
            'predicted_evacuees' => $predictedPersons,
            'predicted_center_occupancy' => $predictedOccupancy,
            'predicted_resources_needed' => $predictedResourceCost,
            'model_used' => 'PHP-ML Linear Regression (LeastSquares)',
            'mae_score' => $evaluation['mae'] ?? null,
            'r2_score' => $evaluation['r2'] ?? null,
            'input_payload' => [
                'rainfall_mm' => $rainfallMm,
                'wind_speed_kph' => $windSpeedKph,
                'training_event_count' => $data['event_count'],
                'used_default_ratios' => $usedDefaults,
            ],
            'generated_at' => now(),
        ]);
    }

    private function historicalAverageCostPerPerson(bool &$usedDefaults): float
    {
        $grouped = PredictionDataset::whereIn('parameter', ['actual_resource_cost', 'actual_persons_displaced'])
            ->get()->groupBy('evacuation_event_id');

        $ratios = [];
        foreach ($grouped as $params) {
            $byParam = $params->keyBy('parameter');
            $persons = (float) ($byParam->get('actual_persons_displaced')->value ?? 0);
            if ($byParam->has('actual_resource_cost') && $persons > 0) {
                $ratios[] = (float) $byParam['actual_resource_cost']->value / $persons;
            }
        }

        if (count($ratios) === 0) {
            $usedDefaults = true;

            return self::DEFAULT_COST_PER_PERSON;
        }

        return array_sum($ratios) / count($ratios);
    }

    /**
     * R² isn't a confirmed built-in PHP-ML metric (unlike meanAbsoluteError,
     * which is), so this is computed directly with the standard formula:
     * 1 - (sum of squared residuals / total sum of squares).
     */
    private function computeR2(array $actual, array $predicted): ?float
    {
        $n = count($actual);
        if ($n === 0) {
            return null;
        }

        $mean = array_sum($actual) / $n;
        $ssTot = array_sum(array_map(fn ($a) => ($a - $mean) ** 2, $actual));

        if ($ssTot == 0.0) {
            return null; // no variance in actual outcomes -- R^2 is undefined, not zero
        }

        $ssRes = 0.0;
        foreach ($actual as $i => $a) {
            $ssRes += ($a - $predicted[$i]) ** 2;
        }

        return round(1 - ($ssRes / $ssTot), 4);
    }

    private function upsertDataset(int $eventId, string $parameter, float $value): void
    {
        PredictionDataset::updateOrCreate(
            ['evacuation_event_id' => $eventId, 'parameter' => $parameter],
            ['value' => $value, 'unit' => null, 'recorded_at' => now()]
        );
    }
}

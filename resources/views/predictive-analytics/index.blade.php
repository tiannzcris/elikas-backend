@extends('layouts.app')

@section('title', 'Predictive analytics')
@section('nav-analytics', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Predictive analytics</h1>
    <p class="text-sm text-gray-500 mb-6">Forecasts expected evacuee volume from rainfall and wind speed, based on this system's own historical events.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4 max-w-2xl"></div>

    <div id="status-card" class="bg-white border border-gray-200 rounded-xl p-4 mb-6 max-w-2xl">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-7 h-7 rounded-md bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-database text-blue-500" style="font-size: 15px;" aria-hidden="true"></i>
            </div>
            <p class="text-sm font-medium">Training data status</p>
        </div>
        <div id="status-content" class="text-sm text-gray-600">Loading...</div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-8 max-w-2xl">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-md bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-trending-up text-orange-500" style="font-size: 15px;" aria-hidden="true"></i>
            </div>
            <p class="text-sm font-medium">Generate a forecast</p>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-3">
            <div>
                <label class="text-xs text-gray-600 block mb-1">Forecasted rainfall (mm)</label>
                <input type="number" step="0.1" id="rainfall_mm" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-600 block mb-1">Forecasted max wind speed (kph)</label>
                <input type="number" step="0.1" id="wind_speed_kph" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-600 block mb-1">Signal level</label>
                <div id="signal-level-display" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500">
                    Enter wind speed
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="text-xs text-gray-600 block mb-1">Link to an event (optional)</label>
            <select id="evacuation_event_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">None -- standalone what-if scenario</option>
            </select>
        </div>
        <button id="generate-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            Generate forecast
        </button>
    </div>

    <div id="accuracy-chart-card" class="hidden bg-white border border-gray-200 rounded-xl p-4 mb-8 max-w-3xl">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-md bg-purple-50 flex items-center justify-center shrink-0">
                <i class="ti ti-chart-line text-purple-500" style="font-size: 15px;" aria-hidden="true"></i>
            </div>
            <p class="text-sm font-medium">Historical forecast accuracy</p>
        </div>
        <div style="position: relative; width: 100%; height: 260px;">
            <canvas id="accuracyChart" role="img" aria-label="Line chart comparing actual vs predicted evacuees per historical event">Loading chart data</canvas>
        </div>
    </div>

    <p class="text-sm font-medium text-gray-700 mb-3">Forecast history</p>
    <div id="predictions-list" class="flex flex-col gap-2"></div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    // PAGASA's actual public storm warning signal thresholds -- informational
    // only, not fed into the regression as a separate input, since it's
    // just a re-expression of wind speed and would be redundant/collinear
    // with the wind_speed_kph feature the model already uses.
    function computeSignalLevel(windKph) {
        if (windKph >= 185) return 'Signal 5';
        if (windKph >= 118) return 'Signal 4';
        if (windKph >= 89) return 'Signal 3';
        if (windKph >= 62) return 'Signal 2';
        if (windKph >= 39) return 'Signal 1';
        return 'No signal';
    }

    document.getElementById('wind_speed_kph').addEventListener('input', (e) => {
        const display = document.getElementById('signal-level-display');
        const value = Number(e.target.value);
        display.textContent = e.target.value.trim() === '' ? 'Enter wind speed' : computeSignalLevel(value);
    });

    let accuracyChartInstance = null;

    async function loadStatus() {
        const result = await Api.get('/analytics/status');
        const s = result.data;
        const box = document.getElementById('status-content');

        if (! s.can_predict) {
            box.innerHTML = `
                <p class="text-amber-600">Only ${s.historical_event_count} completed disaster event(s) on record --
                at least ${s.minimum_to_predict} are needed before a forecast can be generated.
                Close out disaster events as they conclude to build this up over time.</p>`;
            document.getElementById('generate-btn').disabled = true;
            document.getElementById('generate-btn').classList.add('opacity-50', 'cursor-not-allowed');
            return;
        }

        let evalText = `<p class="text-amber-600 mt-1">Not enough events yet (need ${s.minimum_to_evaluate}) to report accuracy metrics -- forecasts will still generate, without a confidence score attached.</p>`;
        if (s.evaluation) {
            evalText = `
                <p class="mt-1">Model evaluated via leave-one-out cross-validation across ${s.evaluation.sample_count} historical events:</p>
                <p class="mt-1">Mean Absolute Error: <strong>${s.evaluation.mae.toFixed(1)} persons</strong>
                ${s.evaluation.r2 !== null ? ` &middot; R&sup2;: <strong>${s.evaluation.r2.toFixed(3)}</strong>` : ' &middot; R&sup2;: undetermined (no variance in historical outcomes)'}</p>`;

            if (s.evaluation.per_event && s.evaluation.per_event.length > 0) {
                document.getElementById('accuracy-chart-card').classList.remove('hidden');
                if (accuracyChartInstance) accuracyChartInstance.destroy();

                accuracyChartInstance = new Chart(document.getElementById('accuracyChart'), {
                    type: 'line',
                    data: {
                        labels: s.evaluation.per_event.map((e) => e.event_name),
                        datasets: [
                            {
                                label: 'Actual', data: s.evaluation.per_event.map((e) => e.actual),
                                borderColor: '#2563eb', backgroundColor: '#2563eb', tension: 0.3,
                            },
                            {
                                label: 'Forecast', data: s.evaluation.per_event.map((e) => e.predicted),
                                borderColor: '#f97316', backgroundColor: '#f97316', borderDash: [5, 5], tension: 0.3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true, grid: { color: '#e1e0d9' } }, x: { grid: { display: false } } },
                    },
                });
            }
        }

        box.innerHTML = `<p>${s.historical_event_count} completed disaster event(s) available for training.</p>${evalText}`;
    }

    (async () => {
        try {
            await loadStatus();

            const events = await Api.get('/evacuation-events');
            document.getElementById('evacuation_event_id').insertAdjacentHTML('beforeend',
                events.data.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join(''));

            await loadPredictions();
        } catch (error) {
            showFormErrors(error);
        }
    })();

    async function loadPredictions() {
        const result = await Api.get('/predictions');
        const predictions = result.data.data;

        document.getElementById('predictions-list').innerHTML = predictions.length === 0
            ? '<p class="text-gray-400 text-sm text-center py-8">No forecasts generated yet.</p>'
            : predictions.map((p) => `
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium">${p.evacuation_event?.name ?? 'Standalone forecast'}</p>
                        <p class="text-xs text-gray-400">${new Date(p.generated_at).toLocaleString()}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm mb-2">
                        <div><p class="text-xs text-gray-500">Predicted evacuees</p><p class="font-medium">${p.predicted_evacuees}</p></div>
                        <div><p class="text-xs text-gray-500">Predicted center occupancy</p><p class="font-medium">${p.predicted_center_occupancy}</p></div>
                        <div><p class="text-xs text-gray-500">Estimated resource cost</p><p class="font-medium">&#8369;${Number(p.predicted_resources_needed).toLocaleString()}</p></div>
                    </div>
                    <p class="text-xs text-gray-400">
                        Input: ${p.input_payload.rainfall_mm}mm rainfall, ${p.input_payload.wind_speed_kph}kph wind &middot;
                        trained on ${p.input_payload.training_event_count} historical event(s)
                        ${p.mae_score !== null ? ` &middot; MAE ${Number(p.mae_score).toFixed(1)}` : ''}
                        ${p.input_payload.used_default_ratios ? ' &middot; <span class="text-amber-600">occupancy/cost used default ratios (no cost history yet)</span>' : ''}
                    </p>
                </div>
            `).join('');
    }

    document.getElementById('generate-btn').addEventListener('click', async () => {
        const rainfallInput = document.getElementById('rainfall_mm');
        const windInput = document.getElementById('wind_speed_kph');

        // Checked as blank text, not as a number -- Number('') is 0 in
        // JavaScript, which would otherwise let an empty field silently
        // submit as "0mm rainfall" instead of stopping with a clear message
        // that the field needs to actually be filled in first.
        if (rainfallInput.value.trim() === '' || windInput.value.trim() === '') {
            showFormErrors({ message: 'Enter both forecasted rainfall and wind speed before generating a forecast.' });
            return;
        }

        const button = document.getElementById('generate-btn');
        button.disabled = true;
        button.textContent = 'Generating...';

        try {
            await Api.post('/predictions', {
                rainfall_mm: Number(rainfallInput.value),
                wind_speed_kph: Number(windInput.value),
                evacuation_event_id: document.getElementById('evacuation_event_id').value || null,
            });
            await loadPredictions();
        } catch (error) {
            showFormErrors(error);
        } finally {
            button.disabled = false;
            button.textContent = 'Generate forecast';
        }
    });
</script>
@endsection

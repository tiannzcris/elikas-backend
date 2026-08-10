@extends('layouts.app')

@section('title', 'Predictive analytics')
@section('nav-analytics', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Predictive analytics</h1>
    <p class="text-sm text-gray-500 mb-6">Forecasts expected evacuee volume from rainfall and wind speed, based on this system's own historical events.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div id="latest-stats-row" class="hidden grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Predicted evacuees</p>
                <p id="latest-evacuees" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Latest forecast</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Predicted occupancy</p>
                <p id="latest-occupancy" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Across centers</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-building text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Estimated resource cost</p>
                <p id="latest-cost" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="latest-cost-note" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-currency-peso text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #A855F7;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Signal level</p>
                <p id="latest-signal" class="text-lg font-bold text-gray-800">&mdash;</p>
                <p id="latest-signal-note" class="text-xs text-gray-400 italic mt-1">From forecasted wind speed</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                <i class="ti ti-wind text-purple-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0 flex flex-col gap-6">
            <div id="status-card" class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-md bg-blue-50 flex items-center justify-center shrink-0">
                        <i class="ti ti-database text-blue-500" style="font-size: 15px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm font-medium">Training data status</p>
                </div>
                <div id="status-content" class="text-sm text-gray-600">Loading...</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-md bg-orange-50 flex items-center justify-center shrink-0">
                        <i class="ti ti-trending-up text-orange-500" style="font-size: 15px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm font-medium">Generate a forecast</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
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

            <div id="accuracy-chart-card" class="hidden bg-white border border-gray-200 rounded-xl p-4">
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

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-md bg-teal-50 flex items-center justify-center shrink-0">
                        <i class="ti ti-chart-histogram text-teal-600" style="font-size: 15px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm font-medium">Rainfall / wind forecast (SARIMA)</p>
                </div>
                <p class="text-xs text-gray-500 mb-3">
                    A separate, real time-series model trained on imported historical PAGASA weather
                    readings -- unlike the single rainfall/wind forecast above, this detects
                    seasonal patterns across many past days. Requires weather data to be imported
                    first (see <code class="bg-gray-100 px-1 rounded">php artisan weather:import</code>).
                </p>

                <div id="sarima-empty-state" class="hidden text-center py-8 text-sm text-gray-400">
                    No weather history imported yet. Import PAGASA data (or run
                    <code class="bg-gray-100 px-1 rounded">php artisan weather:import ... --sample</code>
                    for test data) before a forecast can be generated.
                </div>

                <div id="sarima-form-wrap">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
                        <div>
                            <label class="text-xs text-gray-600 block mb-1">Metric</label>
                            <select id="sarima-metric" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="rainfall_mm">Rainfall (mm)</option>
                                <option value="wind_speed_kph">Wind speed (kph)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 block mb-1">Horizon (periods)</label>
                            <input type="number" id="sarima-horizon" value="14" min="1" max="60" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="flex items-end">
                            <button id="sarima-generate-btn" class="w-full bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2">
                                Generate forecast
                            </button>
                        </div>
                    </div>
                </div>

                <div id="sarima-sample-banner" class="hidden bg-amber-50 text-amber-700 text-xs rounded-lg p-3 mb-3 flex gap-2">
                    <i class="ti ti-alert-triangle shrink-0" style="font-size: 14px;" aria-hidden="true"></i>
                    <span>This forecast is based on SAMPLE/TEST weather data, not real PAGASA records -- for pipeline testing only. Do not use for actual planning decisions.</span>
                </div>

                <div id="sarima-unavailable-banner" class="hidden bg-gray-50 text-gray-500 text-xs rounded-lg p-3 mb-3"></div>

                <div id="sarima-chart-wrap" class="hidden">
                    <div style="position: relative; width: 100%; height: 240px;">
                        <canvas id="sarimaChart" role="img" aria-label="Line chart of historical and forecasted weather readings"></canvas>
                    </div>
                    <p id="sarima-diagnostics" class="text-xs text-gray-400 mt-2"></p>
                </div>
            </div>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <p class="text-sm font-medium text-gray-700">Forecast history</p>
                    <input id="history-search" type="text" placeholder="Search by event..."
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs w-full sm:w-48">
                </div>
                <div id="predictions-list" class="flex flex-col gap-2"></div>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Model confidence</p>
                <div id="confidence-content"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-md bg-purple-500 flex items-center justify-center shrink-0">
                        <i class="ti ti-sparkles text-white" style="font-size: 13px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-700">AI recommendations</p>
                </div>
                <div id="recommendations-list" class="flex flex-col gap-3"></div>
                <p class="text-xs text-gray-400 mt-3">Rule-based guidance computed from the latest forecast's own numbers and current center capacity -- not a separate AI model.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">PAGASA signal reference</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between"><span class="text-gray-600">Signal 1</span><span class="text-gray-800 font-medium">39&ndash;61 kph</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Signal 2</span><span class="text-gray-800 font-medium">62&ndash;88 kph</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Signal 3</span><span class="text-gray-800 font-medium">89&ndash;117 kph</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Signal 4</span><span class="text-gray-800 font-medium">118&ndash;184 kph</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Signal 5</span><span class="text-gray-800 font-medium">185+ kph</span></div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Computed client-side from the wind speed you enter above -- not a separate model input.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Recent activity</p>
                <div id="activity-timeline" class="space-y-4 text-xs"></div>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-2.5">
                <i class="ti ti-info-circle text-blue-500 shrink-0" style="font-size: 16px;" aria-hidden="true"></i>
                <p class="text-xs text-blue-800">This model forecasts total evacuee volume citywide from rainfall and wind speed. It does not currently produce per-barangay risk levels or hazard-type breakdowns -- that would need barangay-level population figures and a separate risk-scoring model, neither of which exist in this system yet.</p>
            </div>
        </div>
    </div>
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
    let allPredictions = [];
    let allCenters = [];
    let sarimaChartInstance = null;
    const user = Api.getUser();
    const isAdministrator = user && user.role === 'administrator';
    // Same access level as generating a linear-regression forecast --
    // barangay officials can view but not generate.
    const canGenerateForecasts = user && user.role !== 'barangay_official';

    // Rule-based recommendations derived from the latest forecast's own
    // numbers plus current center capacity -- deterministic thresholds,
    // not a separate recommendation model. Every figure quoted here is
    // real data already shown elsewhere on this page/system; nothing is
    // invented copy.
    function buildRecommendations(prediction, centers) {
        if (! prediction) return [];

        const recs = [];
        const totalCapacity = centers.reduce((sum, c) => sum + (c.capacity_persons ?? 0), 0);
        const totalAvailable = centers.reduce((sum, c) => sum + Math.max((c.capacity_persons ?? 0) - (c.current_occupancy ?? 0), 0), 0);
        const predictedEvacuees = prediction.predicted_evacuees ?? 0;

        if (totalCapacity > 0 && predictedEvacuees > totalAvailable) {
            recs.push({
                icon: 'ti-alert-triangle', color: '#EF4444', bg: '#FEE2E2',
                title: 'Pre-position additional resources',
                body: `Predicted evacuees (${predictedEvacuees}) exceed currently available center capacity (${totalAvailable}). Consider activating standby centers or coordinating relief goods ahead of arrival.`,
                link: { href: '/evacuation-centers', label: 'Manage centers' },
            });
        } else if (totalCapacity > 0 && predictedEvacuees > totalAvailable * 0.7) {
            recs.push({
                icon: 'ti-building', color: '#F59E0B', bg: '#FEF3C7',
                title: 'Monitor center capacity closely',
                body: `Predicted evacuees (${predictedEvacuees}) would use most of the currently available capacity (${totalAvailable}). Watch occupancy as centers fill up.`,
                link: { href: '/evacuation-centers', label: 'View centers' },
            });
        } else if (totalCapacity > 0) {
            recs.push({
                icon: 'ti-circle-check', color: '#22C55E', bg: '#DCFCE7',
                title: 'Capacity looks sufficient',
                body: `Available center capacity (${totalAvailable}) currently covers the predicted evacuee volume (${predictedEvacuees}).`,
            });
        }

        const rainfall = Number(prediction.input_payload?.rainfall_mm ?? 0);
        if (rainfall >= 100) {
            recs.push({
                icon: 'ti-droplet', color: '#2563EB', bg: '#DBEAFE',
                title: 'Prepare for possible flooding',
                body: `Forecasted rainfall of ${rainfall}mm is high. Coordinate with barangays in mapped flood-prone zones.`,
                link: { href: '/gis-map', label: 'View hazard map' },
            });
        } else if (rainfall >= 50) {
            recs.push({
                icon: 'ti-cloud-rain', color: '#0891B2', bg: '#CFFAFE',
                title: 'Watch rainfall accumulation',
                body: `Forecasted rainfall of ${rainfall}mm is moderate. Advise low-lying barangays to stay alert.`,
            });
        }

        const wind = Number(prediction.input_payload?.wind_speed_kph ?? 0);
        if (wind >= 62) {
            recs.push({
                icon: 'ti-wind', color: '#7C3AED', bg: '#EDE9FE',
                title: 'Consider issuing an evacuation advisory',
                body: `Forecasted wind speed (${wind}kph) corresponds to PAGASA ${computeSignalLevel(wind)}. Consider sending an alert to residents in at-risk barangays.`,
                link: { href: '/alerts/create', label: 'Send an alert' },
            });
        }

        if (prediction.r2_score === null || prediction.r2_score < 0.4) {
            recs.push({
                icon: 'ti-alert-circle', color: '#F59E0B', bg: '#FEF3C7',
                title: 'Treat this forecast as preliminary',
                body: prediction.r2_score === null
                    ? 'Not enough historical events yet to establish model confidence -- cross-check with PAGASA\'s official bulletin before acting on this number alone.'
                    : `Model confidence is low (R² ${prediction.r2_score.toFixed(3)}) -- cross-check with PAGASA's official bulletin before acting on this number alone.`,
            });
        }

        return recs;
    }

    function renderRecommendations() {
        const box = document.getElementById('recommendations-list');
        const latest = allPredictions[0];

        if (! latest) {
            box.innerHTML = '<p class="text-xs text-gray-400">Generate a forecast to see recommendations.</p>';
            return;
        }

        const recs = buildRecommendations(latest, allCenters);
        box.innerHTML = recs.length === 0
            ? '<p class="text-xs text-gray-400">No specific concerns flagged for this forecast.</p>'
            : recs.map((r) => `
                <div class="flex gap-2.5">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0" style="background:${r.bg}">
                        <i class="ti ${r.icon}" style="font-size:14px; color:${r.color}" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-700">${r.title}</p>
                        <p class="text-xs text-gray-500 mt-0.5">${r.body}</p>
                        ${r.link ? `<a href="${r.link.href}" class="text-xs text-brand hover:underline">${r.link.label} &rarr;</a>` : ''}
                    </div>
                </div>`).join('');
    }

    function renderConfidence(evaluation) {
        const box = document.getElementById('confidence-content');

        if (! evaluation || evaluation.r2 === null || evaluation.r2 === undefined) {
            box.innerHTML = `
                <p class="text-2xl font-bold text-gray-300 mb-1">&mdash;</p>
                <p class="text-xs text-gray-400">Not enough historical events with variation yet to compute R&sup2;. Forecasts still generate, just without a confidence score attached.</p>`;
            return;
        }

        // R² can technically go negative (worse than just predicting the
        // mean) -- clamped to 0% for display rather than shown as a
        // confusing negative percentage, with the raw score still visible.
        const pct = Math.max(0, Math.round(evaluation.r2 * 100));
        const color = evaluation.r2 >= 0.7 ? '#22C55E' : evaluation.r2 >= 0.4 ? '#F59E0B' : '#EF4444';
        const label = evaluation.r2 >= 0.7 ? 'High reliability' : evaluation.r2 >= 0.4 ? 'Moderate reliability' : 'Low reliability';

        box.innerHTML = `
            <p class="text-3xl font-bold mb-1" style="color:${color}">${pct}%</p>
            <p class="text-xs font-medium mb-2" style="color:${color}">${label}</p>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mb-3">
                <div class="h-1.5 rounded-full" style="width:${pct}%; background:${color}"></div>
            </div>
            <p class="text-xs text-gray-500">R&sup2; ${evaluation.r2.toFixed(3)} &middot; MAE ${evaluation.mae.toFixed(1)} persons</p>
            <p class="text-xs text-gray-400 mt-1">Evaluated via leave-one-out cross-validation across ${evaluation.sample_count} historical event(s).</p>`;
    }

    async function loadStatus() {
        const result = await Api.get('/analytics/status');
        const s = result.data;
        const box = document.getElementById('status-content');

        renderConfidence(s.evaluation);

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

    function renderLatestStats() {
        if (allPredictions.length === 0) return;

        const latest = allPredictions[0];
        document.getElementById('latest-stats-row').classList.remove('hidden');
        document.getElementById('latest-evacuees').textContent = latest.predicted_evacuees ?? '—';
        document.getElementById('latest-occupancy').textContent = latest.predicted_center_occupancy ?? '—';
        document.getElementById('latest-cost').textContent = latest.predicted_resources_needed !== null
            ? `₱${Number(latest.predicted_resources_needed).toLocaleString()}` : '—';
        document.getElementById('latest-cost-note').textContent = latest.input_payload?.used_default_ratios
            ? 'Default ratios (no cost history yet)' : 'From historical cost data';
        document.getElementById('latest-signal').textContent = latest.input_payload?.wind_speed_kph !== undefined
            ? computeSignalLevel(Number(latest.input_payload.wind_speed_kph)) : '—';
    }

    function renderPredictionsList() {
        const query = document.getElementById('history-search').value.trim().toLowerCase();
        const filtered = allPredictions.filter((p) =>
            ! query || (p.evacuation_event?.name ?? 'standalone forecast').toLowerCase().includes(query));

        document.getElementById('predictions-list').innerHTML = filtered.length === 0
            ? '<p class="text-gray-400 text-sm text-center py-8 bg-white border border-gray-200 rounded-xl">No forecasts match this filter.</p>'
            : filtered.map((p) => `
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium">${p.evacuation_event?.name ?? 'Standalone forecast'}</p>
                        <p class="text-xs text-gray-400">${new Date(p.generated_at).toLocaleString()}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm mb-2">
                        <div><p class="text-xs text-gray-500">Predicted evacuees</p><p class="font-medium">${p.predicted_evacuees}</p></div>
                        <div><p class="text-xs text-gray-500">Predicted center occupancy</p><p class="font-medium">${p.predicted_center_occupancy}</p></div>
                        <div><p class="text-xs text-gray-500">Estimated resource cost</p><p class="font-medium">&#8369;${Number(p.predicted_resources_needed).toLocaleString()}</p></div>
                    </div>
                    <p class="text-xs text-gray-400">
                        Input: ${p.input_payload.rainfall_mm}mm rainfall, ${p.input_payload.wind_speed_kph}kph wind &middot;
                        trained on ${p.input_payload.training_event_count} historical event(s)
                        ${p.mae_score !== null ? ` &middot; MAE ${Number(p.mae_score).toFixed(1)}` : ''}
                        ${p.r2_score !== null ? ` &middot; R&sup2; ${Number(p.r2_score).toFixed(3)}` : ''}
                        ${p.input_payload.used_default_ratios ? ' &middot; <span class="text-amber-600">occupancy/cost used default ratios (no cost history yet)</span>' : ''}
                    </p>
                </div>
            `).join('');
    }

    async function loadPredictions() {
        const result = await Api.get('/predictions?per_page=100');
        allPredictions = result.data.data;
        renderLatestStats();
        renderPredictionsList();
        renderRecommendations();
    }

    async function loadActivity() {
        if (! isAdministrator) {
            document.getElementById('activity-timeline').innerHTML = '<p class="text-gray-400">Visible to administrators only.</p>';
            return;
        }
        try {
            const result = await Api.get('/system-logs?per_page=200');
            const recent = result.data.data.filter((l) => l.action === 'prediction.generated').slice(0, 6);
            document.getElementById('activity-timeline').innerHTML = recent.length ? recent.map((l) => `
                <div class="flex gap-2.5">
                    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-purple-400"></span>
                    <div class="min-w-0">
                        <p class="text-gray-700 font-medium truncate">${l.description ?? l.action}</p>
                        <p class="text-gray-400">${l.user?.name ?? 'System'} &middot; ${new Date(l.created_at).toLocaleString()}</p>
                    </div>
                </div>`).join('') : '<p class="text-gray-400">No activity recorded yet.</p>';
        } catch (error) {
            document.getElementById('activity-timeline').innerHTML = '<p class="text-gray-400">Unavailable.</p>';
        }
    }

    const METRIC_LABELS = { rainfall_mm: 'Rainfall (mm)', wind_speed_kph: 'Wind speed (kph)' };

    // Renders a single WeatherForecastResource-shaped object (or null to
    // reset back to an empty chart). Fetches the historical readings for
    // the same metric separately, since /weather-forecasts only stores the
    // forecast points themselves, not the training data behind them.
    async function renderSarimaForecast(forecastRow) {
        document.getElementById('sarima-sample-banner').classList.toggle('hidden', ! forecastRow?.is_sample_based);

        if (! forecastRow) {
            document.getElementById('sarima-chart-wrap').classList.add('hidden');
            return;
        }

        let historical = [];
        try {
            const readingsResult = await Api.get('/weather-readings?per_page=200');
            historical = readingsResult.data.data
                .filter((r) => r[forecastRow.metric] !== null)
                .sort((a, b) => a.reading_date.localeCompare(b.reading_date));
        } catch (error) {
            // Chart still renders with just the forecast half if this fails.
        }

        document.getElementById('sarima-chart-wrap').classList.remove('hidden');

        const historicalLabels = historical.map((r) => r.reading_date);
        const historicalValues = historical.map((r) => r[forecastRow.metric]);
        const forecastLabels = forecastRow.forecast_points.map((p) => p.date);
        const forecastValues = forecastRow.forecast_points.map((p) => p.predicted);
        const lowerCi = forecastRow.forecast_points.map((p) => p.lower_ci);
        const upperCi = forecastRow.forecast_points.map((p) => p.upper_ci);

        const labels = [...historicalLabels, ...forecastLabels];
        // Padded with nulls so each dataset only draws over its own half of
        // the shared label axis -- Chart.js skips null points by default.
        const historicalPadded = [...historicalValues, ...forecastLabels.map(() => null)];
        const forecastPadded = [...historicalLabels.map(() => null), ...forecastValues];
        const lowerPadded = [...historicalLabels.map(() => null), ...lowerCi];
        const upperPadded = [...historicalLabels.map(() => null), ...upperCi];

        if (sarimaChartInstance) sarimaChartInstance.destroy();
        sarimaChartInstance = new Chart(document.getElementById('sarimaChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Historical', data: historicalPadded,
                        borderColor: '#2563eb', backgroundColor: '#2563eb', pointRadius: 0, tension: 0.3,
                    },
                    {
                        label: 'Forecast', data: forecastPadded,
                        borderColor: '#f97316', backgroundColor: '#f97316', borderDash: [5, 5], pointRadius: 2, tension: 0.3,
                    },
                    {
                        label: 'Upper bound', data: upperPadded,
                        borderColor: 'transparent', backgroundColor: 'rgba(249,115,22,0.12)', pointRadius: 0, fill: '+1',
                    },
                    {
                        label: 'Lower bound', data: lowerPadded,
                        borderColor: 'transparent', backgroundColor: 'rgba(249,115,22,0.12)', pointRadius: 0, fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { position: 'bottom', labels: { filter: (item) => item.text !== 'Upper bound' && item.text !== 'Lower bound' } } },
                scales: { y: { beginAtZero: true, grid: { color: '#e1e0d9' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } } },
            },
        });

        const d = forecastRow.diagnostics;
        document.getElementById('sarima-diagnostics').textContent = d
            ? `${d.model ?? ''} · AIC ${d.aic} · n=${d.n_observations} observations`.trim()
            : '';
    }

    async function loadSarima() {
        if (! canGenerateForecasts) {
            document.getElementById('sarima-form-wrap').classList.add('hidden');
        }

        try {
            const readingsResult = await Api.get('/weather-readings?per_page=1');
            const hasReadings = (readingsResult.data.meta?.total ?? readingsResult.data.data.length) > 0;

            document.getElementById('sarima-empty-state').classList.toggle('hidden', hasReadings);
            document.getElementById('sarima-form-wrap').classList.toggle('hidden', ! hasReadings || ! canGenerateForecasts);

            if (! hasReadings) return;

            const forecastsResult = await Api.get('/weather-forecasts?per_page=1');
            await renderSarimaForecast(forecastsResult.data.data[0] ?? null);
        } catch (error) {
            document.getElementById('sarima-empty-state').classList.remove('hidden');
            document.getElementById('sarima-empty-state').textContent = 'Could not load weather data.';
        }
    }

    document.getElementById('sarima-generate-btn')?.addEventListener('click', async () => {
        const button = document.getElementById('sarima-generate-btn');
        const metric = document.getElementById('sarima-metric').value;
        const horizon = Number(document.getElementById('sarima-horizon').value) || 14;

        document.getElementById('sarima-unavailable-banner').classList.add('hidden');
        button.disabled = true;
        button.textContent = 'Generating...';

        try {
            const result = await Api.post('/weather-forecasts', { metric, horizon });
            await renderSarimaForecast(result.data);
        } catch (error) {
            if (error.status === 503) {
                // SARIMA service not configured/unreachable -- an expected
                // state before the Python microservice is deployed on the
                // VPS, not a bug. Shown inline rather than via the generic
                // form-errors box, since it's specific to this one card.
                document.getElementById('sarima-unavailable-banner').textContent = error.message;
                document.getElementById('sarima-unavailable-banner').classList.remove('hidden');
            } else {
                showFormErrors(error);
            }
        } finally {
            button.disabled = false;
            button.textContent = 'Generate forecast';
        }
    });

    document.getElementById('history-search').addEventListener('input', renderPredictionsList);

    (async () => {
        try {
            await loadStatus();

            const events = await Api.get('/evacuation-events');
            document.getElementById('evacuation_event_id').insertAdjacentHTML('beforeend',
                events.data.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join(''));

            const centersResult = await Api.get('/public/evacuation-centers');
            allCenters = centersResult.data;

            await loadPredictions();
            await loadActivity();
            await loadSarima();
        } catch (error) {
            showFormErrors(error);
        }
    })();

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
            await loadActivity();
        } catch (error) {
            showFormErrors(error);
        } finally {
            button.disabled = false;
            button.textContent = 'Generate forecast';
        }
    });
</script>
@endsection

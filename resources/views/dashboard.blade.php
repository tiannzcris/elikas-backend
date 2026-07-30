@extends('layouts.app')

@section('title', 'Dashboard')
@section('nav-dashboard', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Dashboard</h1>
    <p class="text-sm text-gray-500 mb-6">Welcome back. Here's what's registered so far.</p>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total evacuees</p>
                <p id="stat-families" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Families registered</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Active centers</p>
                <p id="stat-centers" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Facilities in use</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-building text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Predicted influx</p>
                <p id="stat-predicted" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Latest forecast</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-trending-up text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Centers at risk</p>
                <p id="stat-at-risk" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Near or above capacity</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div id="chart-card" class="hidden bg-white border border-gray-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-6 h-6 rounded-md bg-blue-500 flex items-center justify-center">
                <i class="ti ti-chart-bar text-white" style="font-size: 14px;" aria-hidden="true"></i>
            </div>
            <p class="text-sm text-gray-600">Persons displaced by event</p>
        </div>
        <div style="position: relative; width: 100%; height: 220px;">
            <canvas id="eventsChart" role="img" aria-label="Bar chart of persons displaced per disaster event">Loading chart data</canvas>
        </div>
    </div>

    <a href="/families/create"
        class="inline-block bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
        + Register a family
    </a>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    (async () => {
        try {
            const result = await Api.get('/families?per_page=1');
            document.getElementById('stat-families').textContent = result.data.meta.total;
        } catch (error) {
            document.getElementById('stat-families').textContent = '0';
        }
    })();

    (async () => {
        try {
            // Reuses /public/evacuation-centers rather than the staff
            // /evacuation-centers lookup, specifically because the public
            // endpoint includes occupancy_percent (needed for "at risk"
            // below) while the staff lookup only returns id/name/status --
            // one call instead of fetching centers twice.
            const result = await Api.get('/public/evacuation-centers');
            const activeCount = result.data.filter((c) => c.status === 'active').length;
            document.getElementById('stat-centers').textContent = `${activeCount} / ${result.data.length}`;

            const atRiskCount = result.data.filter((c) => c.occupancy_percent !== null && c.occupancy_percent >= 90).length;
            document.getElementById('stat-at-risk').textContent = atRiskCount;
        } catch (error) {
            document.getElementById('stat-centers').textContent = '0';
            document.getElementById('stat-at-risk').textContent = '0';
        }
    })();

    (async () => {
        try {
            const result = await Api.get('/predictions?per_page=1');
            const latest = result.data.data[0];
            document.getElementById('stat-predicted').textContent = latest
                ? latest.predicted_evacuees
                : 'None yet';
        } catch (error) {
            document.getElementById('stat-predicted').textContent = 'None yet';
        }
    })();

    (async () => {
        try {
            const result = await Api.get('/evacuation-events');
            const events = result.data.filter((e) => e.total_persons_displaced > 0);

            if (events.length === 0) {
                return; // chart card stays hidden -- nothing meaningful to plot yet
            }

            document.getElementById('chart-card').classList.remove('hidden');

            new Chart(document.getElementById('eventsChart'), {
                type: 'bar',
                data: {
                    labels: events.map((e) => e.name),
                    datasets: [{
                        label: 'Persons displaced',
                        data: events.map((e) => e.total_persons_displaced),
                        backgroundColor: '#2a78d6',
                        borderRadius: 4,
                        maxBarThickness: 40,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `Evacuees: ${ctx.parsed.y.toLocaleString()}`,
                            },
                        },
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#e1e0d9' } },
                        x: { grid: { display: false } },
                    },
                },
            });
        } catch (error) {
            // Chart card just stays hidden if this fails -- the three KPI
            // cards above are the essential info and shouldn't be blocked
            // by a chart-specific error.
        }
    })();
</script>
@endsection

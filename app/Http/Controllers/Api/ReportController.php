<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\EvacuationCenter;
use App\Models\EvacuationEvent;
use App\Models\Report;
use App\Models\SystemLog;
use App\Services\Reports\DromicRegionVReportService;
use App\Services\Reports\EcInformationBoardReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::query()
            ->with(['evacuationEvent', 'generator'])
            ->latest('generated_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(ReportResource::collection($reports)->response()->getData(true));
    }

    /**
     * Generates the full DROMIC Region V report scoped to Ligao City's data
     * for one disaster event. See DromicRegionVReportService's docblock for
     * exactly which columns are populated, left untouched, or are known
     * gaps -- this is not a fully autonomous submission, it eliminates the
     * manual data entry and arithmetic, and still wants a human review pass.
     */
    public function generateDromicRegionV(Request $request, DromicRegionVReportService $service)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
        ]);

        $event = EvacuationEvent::findOrFail($validated['evacuation_event_id']);
        $absolutePath = $service->generate($event);
        $relativePath = 'reports/'.basename($absolutePath);

        $report = Report::create([
            'evacuation_event_id' => $event->id,
            'report_type' => 'dromic_region_v',
            'file_format' => 'xlsx',
            'file_path' => $relativePath,
            'generated_by' => $request->user()->id,
            'generated_at' => now(),
        ]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'report.generated',
            'description' => "{$request->user()->name} generated a DROMIC Region V report for \"{$event->name}\".",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new ReportResource($report->load(['evacuationEvent', 'generator'])),
            'Report generated successfully.',
            201
        );
    }

    /**
     * Lightweight JSON preview -- lets the frontend show a barangay
     * breakdown table before committing to generating the actual Excel
     * file, using the exact same computation the real report uses.
     */
    public function previewDromicRegionV(Request $request, DromicRegionVReportService $service)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
        ]);

        $event = EvacuationEvent::findOrFail($validated['evacuation_event_id']);

        return $this->success($service->previewSummary($event));
    }

    public function generateEcBoard(Request $request, EcInformationBoardReportService $service)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
            'evacuation_center_id' => ['required', 'integer', 'exists:evacuation_centers,id'],
        ]);

        $event = EvacuationEvent::findOrFail($validated['evacuation_event_id']);
        $center = EvacuationCenter::findOrFail($validated['evacuation_center_id']);

        // Barangay officials can generate this report, but only for a
        // center in their own barangay -- same scoping pattern already
        // used for evacuee registration elsewhere in this system.
        if ($request->user()->isBarangayOfficial() && $center->barangay_id !== $request->user()->barangay_id) {
            return $this->error('You can only generate this report for an evacuation center in your own barangay.', 403);
        }

        $absolutePath = $service->generate($center, $event);
        $relativePath = 'reports/'.basename($absolutePath);

        $report = Report::create([
            'evacuation_event_id' => $event->id,
            'report_type' => 'ec_information_board',
            'file_format' => 'xlsx',
            'file_path' => $relativePath,
            'generated_by' => $request->user()->id,
            'generated_at' => now(),
        ]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'report.generated',
            'description' => "{$request->user()->name} generated an EC Information Board for \"{$center->name}\".",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new ReportResource($report->load(['evacuationEvent', 'generator'])),
            'Report generated successfully.',
            201
        );
    }

    /**
     * Streams the actual file. Deliberately NOT a public storage URL --
     * evacuee names, PWD/pregnancy status, and contact numbers end up in
     * these files, so downloading requires the same auth:sanctum + role
     * check as every other staff-only endpoint.
     */
    public function download(Report $report)
    {
        $absolutePath = storage_path('app/'.$report->file_path);

        if (! file_exists($absolutePath)) {
            return $this->error('Report file no longer exists on the server.', 404);
        }

        return Response::download($absolutePath, basename($absolutePath));
    }
}

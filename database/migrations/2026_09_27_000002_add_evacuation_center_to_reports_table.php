<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Which center an EC Information Board export is for, so a barangay
    // official can be limited to their own barangay's exports (see
    // ReportController::index()/download()). Null for city-wide reports.
    // Existing exports are backfilled from their file name, which has
    // always been "ec_board_{centerId}_{timestamp}.xlsx" (see
    // EcInformationBoardReportService::generate()).
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('evacuation_center_id')->nullable()->after('evacuation_event_id')
                ->constrained('evacuation_centers')->nullOnDelete();
        });

        $centerIds = DB::table('evacuation_centers')->pluck('id')->flip();

        DB::table('reports')->where('report_type', 'ec_information_board')->get(['id', 'file_path'])
            ->each(function ($report) use ($centerIds) {
                if (preg_match('/ec_board_(\d+)_/', $report->file_path, $m) && $centerIds->has((int) $m[1])) {
                    DB::table('reports')->where('id', $report->id)->update(['evacuation_center_id' => (int) $m[1]]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evacuation_center_id');
        });
    }
};

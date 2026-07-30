<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tracks every generated DROMIC/custom report file so past reports can be
    // re-downloaded instead of re-generated, and so generation is auditable.
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->enum('report_type', ['dromic_region_v', 'dromic_strandee', 'dromic_cccm_idp', 'ec_information_board', 'custom']);
            $table->enum('file_format', ['xlsx', 'pdf']);
            $table->string('file_path', 255);
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('generated_at');
            $table->timestamps();

            $table->index(['evacuation_event_id', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

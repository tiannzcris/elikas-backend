<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Corresponds to the "Strandee" sheet of the DROMIC template. LSIs are
    // tracked by origin location (often a port or terminal), not by barangay,
    // which is why this is a separate table from families/evacuees.
    public function up(): void
    {
        Schema::create('locally_stranded_individuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->string('origin_location', 150)->comment('e.g. port, terminal, or municipality of origin');
            $table->unsignedInteger('families_count')->default(0);
            $table->unsignedInteger('persons_count')->default(0);
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index('evacuation_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locally_stranded_individuals');
    }
};

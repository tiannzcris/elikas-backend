<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Normalizes the "EVACUATION CENTER FACILITIES" block (columns CQ-DU, ~13 facility
    // types) from the DROMIC Region V sheet and the EC Information Board template
    // (Info/Help Desk, Community Kitchen, Bathing Area, Toilet, Handwashing, Laundry,
    // Women/Child-Friendly Space, Health Facility, Prayer Room, Livestock Area, Storage)
    // into rows instead of ~30 flat columns.
    public function up(): void
    {
        Schema::create('evacuation_center_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_center_id')->constrained()->cascadeOnDelete();
            $table->enum('facility_type', [
                'latrine_compost_pit', 'latrine_sealed',
                'toilet_male', 'toilet_female', 'toilet_common',
                'bathing_area_male', 'bathing_area_female', 'bathing_area_common',
                'handwashing_facility', 'laundry_space',
                'women_friendly_space', 'child_friendly_space',
                'health_facility', 'prayer_room', 'community_kitchen',
                'livestock_area', 'camp_management_desk', 'info_board', 'storage_area',
            ]);
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('is_available')->default(true);
            $table->text('concerns_and_needs')->nullable()->comment('Free text field from EC Information Board template');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['evacuation_center_id', 'facility_type'], 'ec_facility_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_center_facilities');
    }
};

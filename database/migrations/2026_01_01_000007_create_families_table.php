<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A family groups evacuees from the same household. This lets the system
    // report both "families displaced" and "persons displaced" as required by
    // DROMIC, without forcing every evacuee to belong to exactly one nuclear family.
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            // No ->constrained() here: the 'evacuees' table doesn't exist yet
            // (families is migration #7, evacuees is #8) -- and evacuees.family_id
            // references families right back, so this is a genuine circular
            // foreign key between the two tables. Plain column here; the real
            // FK constraint is added at the end of the evacuees migration,
            // once both tables exist. See FamilyController in the next step
            // for how the head-of-family row gets attached after both rows exist.
            $table->unsignedBigInteger('head_of_family_evacuee_id')->nullable();
            $table->boolean('is_4ps_beneficiary')->default(false);
            $table->timestamps();

            $table->index(['evacuation_event_id', 'barangay_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Needed so a barangay_official's edit access can be scoped to "centers
    // I created myself" (not just "centers in my barangay" -- two different
    // officials could serve the same barangay over time). Nullable/
    // nullOnDelete: centers created before this column existed, or whose
    // creator's account is later removed, still keep the center itself --
    // this is provenance metadata, not something the center's existence
    // should depend on.
    public function up(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};

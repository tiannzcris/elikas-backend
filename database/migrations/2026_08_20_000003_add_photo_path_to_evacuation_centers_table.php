<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores the relative path returned by Storage::disk('public')->store(),
    // not a full URL -- EvacuationCenter::photo_url builds the actual URL
    // from it on demand, so nothing here breaks if APP_URL ever changes.
    public function up(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};

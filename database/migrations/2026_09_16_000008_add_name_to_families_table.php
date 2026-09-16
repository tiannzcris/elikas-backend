<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A normally-registered family is identified by its head of family
    // (headOfFamily relation) -- this column stays null for those, exactly
    // as before. It exists specifically so EcBoardPlaceholderSyncService's
    // synthetic "bulk entry" family (which has no head of family; see its
    // class docblock) has a clear display label instead of showing up
    // blank everywhere a family's name is shown.
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('evacuation_center_quick_count_id');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};

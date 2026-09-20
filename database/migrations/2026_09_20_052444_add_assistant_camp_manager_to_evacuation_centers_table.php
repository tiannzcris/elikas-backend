<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A second, separate contact person for the center, matching the real
    // EC Information Board template's structure -- same lengths/nullability
    // as the existing camp_manager_name/camp_manager_contact columns right
    // above them, since this is the same kind of optional staff contact
    // info, not a required field.
    public function up(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->string('assistant_camp_manager_name', 150)->nullable()->after('camp_manager_contact');
            $table->string('assistant_camp_manager_contact', 20)->nullable()->after('assistant_camp_manager_name');
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropColumn(['assistant_camp_manager_name', 'assistant_camp_manager_contact']);
        });
    }
};

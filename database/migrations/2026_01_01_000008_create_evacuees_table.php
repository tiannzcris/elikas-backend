<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evacuees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 10)->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->date('date_of_birth');
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated', 'divorced'])->nullable();
            $table->string('contact_number', 20)->nullable();
            // Sectoral / vulnerability flags required by the DROMIC Region V report
            $table->boolean('is_pwd')->default(false);
            $table->string('pwd_type', 100)->nullable();
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('is_lactating')->default(false);
            $table->boolean('is_solo_parent')->default(false);
            $table->boolean('is_indigenous_person')->default(false);
            $table->boolean('is_4ps_beneficiary')->default(false);
            $table->enum('status', ['active', 'returned_home', 'transferred', 'deceased'])->default('active');
            $table->timestamps();

            $table->index(['family_id']);
            $table->index(['barangay_id']);
        });

        // Completes the circular FK between families and evacuees: now that
        // 'evacuees' exists, families.head_of_family_evacuee_id can finally
        // get its real foreign key constraint (added as a plain nullable
        // column in the families migration, since evacuees didn't exist yet
        // at that point).
        Schema::table('families', function (Blueprint $table) {
            $table->foreign('head_of_family_evacuee_id')
                ->references('id')->on('evacuees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Must drop this constraint before dropping 'evacuees' below --
        // otherwise MySQL/MariaDB refuses to drop a table another table's
        // foreign key still points to. Migration rollbacks run in reverse
        // order, so this down() runs before families' down(), which is
        // exactly the order this needs.
        Schema::table('families', function (Blueprint $table) {
            $table->dropForeign(['head_of_family_evacuee_id']);
        });

        Schema::dropIfExists('evacuees');
    }
};

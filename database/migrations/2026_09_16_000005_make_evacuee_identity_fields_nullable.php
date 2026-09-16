<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Enables "placeholder" evacuees for fast, incremental family
    // registration (register a headcount now, fill in each person's real
    // details later). Raw ALTER TABLE MODIFY, not Schema::change() --
    // doctrine/dbal isn't installed in this project, and this exact
    // approach is already the established pattern here (see
    // 2026_08_20_000002_make_evacuation_center_location_optional.php).
    //
    // Sectoral flags go nullable too, not just default(false): a
    // placeholder's flags are genuinely UNKNOWN, not confirmed-false --
    // collapsing "not yet asked" into "no" would silently under-report
    // PWD/pregnant/etc. counts for anyone still incomplete. NULL is
    // excluded from every sectoral/age-sex breakdown automatically (see
    // Evacuee::getAgeBracketAttribute() and the report services' docblocks),
    // the same way `false` always was -- the only change is that "unknown"
    // and "confirmed no" are no longer the same value.
    public function up(): void
    {
        DB::statement('ALTER TABLE evacuees MODIFY first_name VARCHAR(100) NULL');
        DB::statement('ALTER TABLE evacuees MODIFY last_name VARCHAR(100) NULL');
        DB::statement("ALTER TABLE evacuees MODIFY sex ENUM('male','female') NULL");
        DB::statement('ALTER TABLE evacuees MODIFY date_of_birth DATE NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_pwd TINYINT(1) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_pregnant TINYINT(1) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_lactating TINYINT(1) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_solo_parent TINYINT(1) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_indigenous_person TINYINT(1) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_4ps_beneficiary TINYINT(1) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Only safe to reverse if no row currently has a null value in any
        // of these columns -- this fails loudly (not silently corrupting
        // placeholder rows into fabricated data) if any do, same as the
        // down() this migration's approach was modeled on.
        DB::statement('ALTER TABLE evacuees MODIFY first_name VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY last_name VARCHAR(100) NOT NULL');
        DB::statement("ALTER TABLE evacuees MODIFY sex ENUM('male','female') NOT NULL");
        DB::statement('ALTER TABLE evacuees MODIFY date_of_birth DATE NOT NULL');
        DB::statement('ALTER TABLE evacuees MODIFY is_pwd TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE evacuees MODIFY is_pregnant TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE evacuees MODIFY is_lactating TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE evacuees MODIFY is_solo_parent TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE evacuees MODIFY is_indigenous_person TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE evacuees MODIFY is_4ps_beneficiary TINYINT(1) NOT NULL DEFAULT 0');
    }
};

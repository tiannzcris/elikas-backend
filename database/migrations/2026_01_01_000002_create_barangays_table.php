<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('psgc_code', 20)->nullable()->comment('PSA Philippine Standard Geographic Code, useful for official DROMIC exports');
            $table->decimal('centroid_latitude', 10, 7)->nullable();
            $table->decimal('centroid_longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};

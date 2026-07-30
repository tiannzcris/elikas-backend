<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores the GeoJSON actually served to Leaflet/flutter_map. Kept separate
    // from hazard_prone_areas.geometry (the MySQL spatial column used for
    // server-side spatial queries) so the map layer can be styled/versioned
    // independently of the underlying spatial geometry.
    public function up(): void
    {
        Schema::create('map_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hazard_area_id')->constrained('hazard_prone_areas')->cascadeOnDelete();
            $table->string('layer_name', 150);
            $table->enum('layer_type', ['flood', 'landslide', 'lahar', 'storm_surge', 'volcanic_danger_zone', 'evacuation_route']);
            $table->json('geojson_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_layers');
    }
};

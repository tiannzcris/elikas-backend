<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Master list of food/non-food relief items (FFP, rice, hygiene kits,
    // sleeping kits, shelter kits, etc.) referenced by relief_distributions.
    public function up(): void
    {
        Schema::create('relief_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 150);
            $table->enum('category', ['food', 'non_food']);
            $table->string('unit', 30)->comment('e.g. pack, box, piece, kit');
            $table->timestamps();

            $table->unique(['item_name', 'category']);
        });

        // Seed common DROMIC relief items so the dropdown isn't empty on first use
        DB::table('relief_items')->insert([
            ['item_name' => 'Family Food Pack (FFP)', 'category' => 'food', 'unit' => 'pack', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'Rice', 'category' => 'food', 'unit' => 'kg', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'High Energy Biscuits (HEB)', 'category' => 'food', 'unit' => 'box', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'Ready-to-Eat Food (RTEF)', 'category' => 'food', 'unit' => 'pack', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'Hygiene Kit', 'category' => 'non_food', 'unit' => 'kit', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'Sleeping Kit', 'category' => 'non_food', 'unit' => 'kit', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'Shelter Repair Kit', 'category' => 'non_food', 'unit' => 'kit', 'created_at' => now(), 'updated_at' => now()],
            ['item_name' => 'Modular Tent', 'category' => 'non_food', 'unit' => 'piece', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('relief_items');
    }
};

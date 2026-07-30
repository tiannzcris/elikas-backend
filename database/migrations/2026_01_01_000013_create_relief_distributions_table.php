<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relief_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evacuation_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('relief_item_id')->constrained()->restrictOnDelete();
            $table->enum('source', ['dswd', 'lgu', 'ngo', 'other'])->default('lgu');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            // Generated column: total_cost is always quantity * unit_cost, so we let
            // MySQL compute and store it instead of trusting the application layer
            // to keep it in sync -- this also lets DROMIC cost-summary queries use
            // total_cost directly without recomputing it every time.
            $table->decimal('total_cost', 12, 2)->storedAs('quantity * unit_cost')->nullable();
            $table->foreignId('distributed_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('distributed_at');
            $table->timestamps();

            $table->index(['evacuation_event_id', 'relief_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relief_distributions');
    }
};

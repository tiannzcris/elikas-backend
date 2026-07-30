<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores the OUTPUT of a trained regression run: what the model predicted,
    // which is compared later against actual figures in prediction_datasets
    // to compute MAE/R^2 for the methodology section.
    public function up(): void
    {
        Schema::create('analytics_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Null when this is a forward-looking forecast not yet tied to a confirmed event');
            $table->unsignedInteger('predicted_evacuees')->nullable();
            $table->unsignedInteger('predicted_center_occupancy')->nullable();
            $table->decimal('predicted_resources_needed', 12, 2)->nullable();
            $table->string('model_used', 100)->default('PHP-ML Linear Regression');
            $table->decimal('mae_score', 10, 4)->nullable()->comment('Mean Absolute Error at training time');
            $table->decimal('r2_score', 6, 4)->nullable()->comment('R-squared at training time');
            $table->json('input_payload')->nullable()->comment('Raw feature values used to generate this prediction, for auditability');
            $table->dateTime('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_predictions');
    }
};

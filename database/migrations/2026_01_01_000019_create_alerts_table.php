<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 200);
            $table->text('message');
            $table->enum('alert_type', ['typhoon', 'flood', 'volcanic', 'earthquake', 'general_advisory']);
            $table->enum('status', ['draft', 'sent', 'failed'])->default('draft');
            $table->dateTime('date_sent')->nullable();
            $table->timestamps();

            $table->index(['evacuation_event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};

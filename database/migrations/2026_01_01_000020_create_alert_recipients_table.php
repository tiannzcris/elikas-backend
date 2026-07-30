<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // recipient_type carries both the audience (resident/barangay_official/all)
    // and, implicitly, the delivery channel: push notification for the Flutter
    // app (broadcast over Reverb) or SMS via Semaphore for residents without
    // the app installed. recipient_value holds the FCM token or phone number.
    public function up(): void
    {
        Schema::create('alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained()->cascadeOnDelete();
            $table->enum('recipient_type', ['resident_push', 'resident_sms', 'barangay_official', 'all']);
            $table->string('recipient_value', 150)->nullable()->comment('FCM token or phone number; null when recipient_type is all');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->dateTime('date_sent')->nullable();
            $table->timestamps();

            $table->index(['alert_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_recipients');
    }
};

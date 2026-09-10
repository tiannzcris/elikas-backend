<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Previously a failed SMS attempt only ever recorded status='failed',
    // with no reason at all -- SemaphoreSmsService::send() computes a
    // reason ('not_configured' | 'api_error' | 'exception') and, for
    // api_error/exception specifically, the raw Semaphore response body or
    // exception message, but AlertController::smsRecipients() discarded
    // all of it before this column existed. Confirmed as the actual
    // root cause of "100% failed, laravel.log empty" being undiagnosable
    // without a fresh reproduction each time.
    public function up(): void
    {
        Schema::table('alert_recipients', function (Blueprint $table) {
            $table->text('failure_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('alert_recipients', function (Blueprint $table) {
            $table->dropColumn('failure_reason');
        });
    }
};

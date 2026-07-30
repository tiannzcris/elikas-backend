<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the Semaphore SMS gateway (semaphore.co) -- the provider named in
 * Chapter 3. Semaphore is a paid, third-party Philippine SMS API; a student
 * capstone may not have an active account/credits yet, so this service is
 * built to degrade gracefully rather than throw or silently pretend to
 * succeed: with no API key configured, it logs what WOULD have been sent
 * and returns a clear "not_configured" result the caller can surface.
 */
class SemaphoreSmsService
{
    public function send(string $phoneNumber, string $message): array
    {
        $apiKey = config('services.semaphore.api_key');

        if (! $apiKey) {
            Log::info("[SemaphoreSmsService] No API key configured -- would have sent to {$phoneNumber}: \"{$message}\"");

            return ['success' => false, 'reason' => 'not_configured'];
        }

        try {
            $response = Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $phoneNumber,
                'message' => $message,
                'sendername' => config('services.semaphore.sender_name', 'ELIKAS'),
            ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            Log::warning("[SemaphoreSmsService] Semaphore API returned an error for {$phoneNumber}: {$response->body()}");

            return ['success' => false, 'reason' => 'api_error'];
        } catch (\Throwable $e) {
            // Network failure, timeout, etc. -- one failed SMS should never
            // take down the whole alert-sending request.
            Log::error("[SemaphoreSmsService] Exception sending to {$phoneNumber}: {$e->getMessage()}");

            return ['success' => false, 'reason' => 'exception'];
        }
    }
}

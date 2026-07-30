<?php

namespace App\Http\Controllers;

/**
 * Every API controller in E-LIKAS extends this class so that every endpoint,
 * regardless of module, returns the same JSON shape:
 *
 *   { "success": true,  "message": "...", "data": {...} }
 *   { "success": false, "message": "...", "errors": {...} }
 *
 * This matters for the Flutter app and the web dashboard's JS layer: both can
 * share one response-handling function instead of special-casing each endpoint.
 */
abstract class Controller
{
    protected function success(mixed $data = null, string $message = 'Request successful.', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Request failed.', int $code = 400, mixed $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}

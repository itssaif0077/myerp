<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Enterprise JSON API Response Envelope
 * Ensures standardized REST output format across all endpoints.
 */
class Response
{
    public static function json(
        mixed $data = null,
        int $statusCode = 200,
        string $message = 'Success',
        bool $success = true,
        mixed $errors = null
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'success'   => $success,
            'code'      => $statusCode,
            'message'   => $message,
            'data'      => $data,
            'errors'    => $errors,
            'timestamp' => time(),
        ];

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(mixed $data = null, string $message = 'Success'): void
    {
        self::json($data, 200, $message, true);
    }

    public static function created(mixed $data = null, string $message = 'Resource created successfully'): void
    {
        self::json($data, 201, $message, true);
    }

    public static function badRequest(string $message = 'Bad Request', mixed $errors = null): void
    {
        self::json(null, 400, $message, false, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized access'): void
    {
        self::json(null, 401, $message, false);
    }

    public static function forbidden(string $message = 'Access forbidden'): void
    {
        self::json(null, 403, $message, false);
    }

    public static function conflict(string $message = 'Conflict', mixed $data = null): void
    {
        self::json($data, 409, $message, false);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json(null, 404, $message, false);
    }

    public static function unprocessable(string $message = 'Validation failed', mixed $errors = null): void
    {
        self::json(null, 422, $message, false, $errors);
    }

    public static function serverError(string $message = 'Internal server error', mixed $errors = null): void
    {
        self::json(null, 500, $message, false, $errors);
    }
}

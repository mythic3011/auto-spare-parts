<?php

declare(strict_types=1);

/**
 * ResponseHandler - Standardized API response handling
 *
 * Provides consistent JSON response formatting and HTTP status codes
 */
class ResponseHandler
{
    private const RESPONSE_SUCCESS = 'success';
    private const RESPONSE_ERROR = 'error';

    /**
     * Send a success response
     *
     * @param array<string, mixed> $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     */
    public static function success(
        array $data = [],
        string $message = 'Operation successful',
        int $statusCode = 200
    ): never {
        self::sendResponse(
            self::RESPONSE_SUCCESS,
            $statusCode,
            $message,
            $data
        );
    }

    /**
     * Send an error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array<string, mixed> $errors Additional error details
     */
    public static function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = []
    ): never {
        self::sendResponse(
            self::RESPONSE_ERROR,
            $statusCode,
            $message,
            ['errors' => $errors]
        );
    }

    /**
     * Send a validation error response
     *
     * @param array<string, array<string>> $validationErrors Field-specific errors
     */
    public static function validationError(array $validationErrors): never
    {
        self::error(
            'Validation failed',
            422,
            $validationErrors
        );
    }

    /**
     * Send a created response (201)
     *
     * @param array<string, mixed> $data Created resource data
     * @param string $message Success message
     */
    public static function created(
        array $data = [],
        string $message = 'Resource created successfully'
    ): never {
        self::success($data, $message, 201);
    }

    /**
     * Send a not found response (404)
     *
     * @param string $message Error message
     */
    public static function notFound(string $message = 'Resource not found'): never
    {
        self::error($message, 404);
    }

    /**
     * Send an unauthorized response (401)
     *
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 401);
    }

    /**
     * Send a forbidden response (403)
     *
     * @param string $message Error message
     */
    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, 403);
    }

    /**
     * Internal helper to send response
     *
     * @param string $status Response status
     * @param int $statusCode HTTP status code
     * @param string $message Response message
     * @param array<string, mixed> $data Response data
     */
    private static function sendResponse(
        string $status,
        int $statusCode,
        string $message,
        array $data = []
    ): never {
        // Clean any output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // Build response
        $response = [
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $response['data'] = $data;

        try {
            echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            echo json_encode(['status' => 'error', 'message' => 'JSON encoding error'], JSON_THROW_ON_ERROR);
        }

        exit;
    }
}

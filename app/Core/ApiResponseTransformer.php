<?php

namespace App\Core;

/**
 * JSON envelope builder for API responses.
 *
 * Builds the standard {status, payload, message} envelope as arrays for BC
 * (success()/error()) or as immutable Response values with application/json
 * headers plus HTTP status for pipeline emission (json()/successResponse()/
 * errorResponse()).
 */
class ApiResponseTransformer
{
    public const STATUS_SUCCESS = "success";
    public const STATUS_ERROR = "error";

    /**
     * Core response
     *
     * @param mixed $payload
     * @param string $status
     * @param string $message
     * @return array{
     *     status: string,
     *     payload: mixed,
     *     message: string,
     * }
     */
    protected static function core(mixed $payload, string $status, string $message): array
    {
        return [
            "status" => $status,
            "payload" => $payload,
            "message" => $message,
        ];
    }

    /**
     * Success response
     *
     * @param mixed $payload
     * @param string $message
     * @return array{
     *      status: string,
     *      payload: mixed,
     *      message: string,
     *  }
     */
    public static function success(mixed $payload, string $message = ""): array
    {
        return self::core($payload, self::STATUS_SUCCESS, $message);
    }

    /**
     * Error response
     *
     * @param mixed $payload
     * @param string $message
     * @return array{
     *      status: string,
     *      payload: mixed,
     *      message: string,
     *  }
     */
    public static function error(mixed $payload, string $message = ""): array
    {
        return self::core($payload, self::STATUS_ERROR, $message);
    }

    /**
     * Build a JSON envelope response with correct headers and status.
     *
     * Wraps the {status, payload, message} envelope in an immutable
     * Response with Content-Type application/json and the given HTTP code
     * so controllers can `return ApiResponseTransformer::json(...)` and
     * have RouterResponse emit it with the right status plus headers.
     *
     * @param mixed $payload Envelope payload data.
     * @param string $status Envelope status (success or error).
     * @param int $code HTTP status code.
     * @param string $message Human-readable message.
     * @return Response JSON response with application/json header.
     */
    public static function json(mixed $payload, string $status = self::STATUS_SUCCESS, int $code = 200, string $message = ""): Response
    {
        return Response::json(self::core($payload, $status, $message), $code);
    }

    /**
     * Build a success envelope JSON response.
     *
     * @param mixed $payload Payload data.
     * @param string $message Human-readable message.
     * @param int $code HTTP status code.
     * @return Response JSON response with application/json header.
     */
    public static function successResponse(mixed $payload, string $message = "", int $code = 200): Response
    {
        return self::json($payload, self::STATUS_SUCCESS, $code, $message);
    }

    /**
     * Build an error envelope JSON response.
     *
     * @param mixed $payload Payload data (often null).
     * @param string $message Human-readable message.
     * @param int $code HTTP status code.
     * @return Response JSON response with application/json header.
     */
    public static function errorResponse(mixed $payload = null, string $message = "", int $code = 400): Response
    {
        return self::json($payload, self::STATUS_ERROR, $code, $message);
    }
}

<?php
namespace App\Core;

class ApiResponseTransformer
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

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
            'status' => $status,
            'payload' => $payload,
            'message' => $message
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
    public static function success(mixed $payload, string $message = ''): array
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
    public static function error(mixed $payload, string $message = ''): array
    {
        return self::core($payload, self::STATUS_ERROR, $message);
    }
}

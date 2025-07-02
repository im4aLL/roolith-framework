<?php
namespace App\Core;

use App\Core\Dto\ApiResponseDTO;

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
     * @return ApiResponseDTO
     */
    protected static function core(mixed $payload, string $status, string $message): ApiResponseDTO
    {
        return ApiResponseDTO::create($status, $payload, $message);
    }

    /**
     * Success response
     *
     * @param mixed $payload
     * @param string $message
     * @return ApiResponseDTO
     */
    public static function success(mixed $payload, string $message = ''): ApiResponseDTO
    {
        return self::core($payload, self::STATUS_SUCCESS, $message);
    }

    /**
     * Error response
     *
     * @param mixed $payload
     * @param string $message
     * @return ApiResponseDTO
     */
    public static function error(mixed $payload, string $message = ''): ApiResponseDTO
    {
        return self::core($payload, self::STATUS_ERROR, $message);
    }
}

<?php
namespace App\Core\Dto;

class ApiResponseDTO {
    private function __construct(public string $status, public mixed $payload, public string $message) {}

    public static function create(string $status, mixed $payload, string $message): static
    {
        return new self($status, $payload, $message);
    }
}

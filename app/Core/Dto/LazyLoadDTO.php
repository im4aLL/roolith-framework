<?php
namespace  App\Core\Dto;

class LazyLoadDTO {
    private function __construct(public string $model, public string $foreignKey, public string $localKey) {}

    public static function create(string $model, string $foreignKey, string $localKey): static
    {
        return new self($model, $foreignKey, $localKey);
    }
}

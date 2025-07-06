<?php

namespace App\Core\Dto;

class CompareArraySummaryDTO
{
    public function __construct(
        public int $addedCount,
        public int $removedCount,
        public int $unchangedCount,
    ) {}

    public static function create(int $addedCount, int $removedCount, int $unchangedCount): self
    {
        return new self($addedCount, $removedCount, $unchangedCount);
    }
}

<?php
namespace App\Core\Dto;

class CompareArrayDTO
{
    public function __construct(
        public array $added,
        public array $removed,
        public array $unchanged,
        public CompareArraySummaryDTO $summary,
    ) {}

    public static function create(\stdClass $data): self
    {
        return new self(
            $data->added ?? [],
            $data->removed ?? [],
            $data->unchanged ?? [],
            CompareArraySummaryDTO::create(
                $data->summary->addedCount ?? 0,
                $data->summary->removedCount ?? 0,
                $data->summary->unchangedCount ?? 0,
            )
        );
    }
}

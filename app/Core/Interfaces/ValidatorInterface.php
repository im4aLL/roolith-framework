<?php
namespace App\Core\Interfaces;


/**
 * Contract for input validators.
 */
interface ValidatorInterface
{
    /**
     * Check input validity by rules.
     *
     * @param array<string, mixed> $inputs Input data keyed by field name.
     * @param array<string, ValidatorRulesInterface> $rules Rules per field.
     * @return static Self for chaining.
     */
    public function check(array $inputs, array $rules): static;

    /**
     * Whether the request is valid or not.
     *
     * @return bool True when no errors were recorded.
     */
    public function success(): bool;

    /**
     * Whether request fails in validity check.
     *
     * @return bool True when errors were recorded.
     */
    public function fails(): bool;

    /**
     * Get all errors after validation check.
     *
     * @return array<string, mixed> Errors grouped by field.
     */
    public function errors(): iterable;
}

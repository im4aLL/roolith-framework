<?php
namespace App\Core\Interfaces;


interface ValidatorInterface
{
    /**
     * Check input validity by rules
     *
     * @param array $inputs
     * @param array $rules
     * @return $this
     */
    public function check(array $inputs, array $rules): static;

    /**
     * Whether the request is valid or not
     *
     * @return bool
     */
    public function success(): bool;

    /**
     * Whether request fails in validity check
     *
     * @return bool
     */
    public function fails(): bool;

    /**
     * Get all errors after validation check
     *
     * @return iterable
     */
    public function errors(): iterable;
}

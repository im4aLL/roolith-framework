<?php
namespace App\Core\Interfaces;


interface ValidatorRulesInterface
{
    /**
     * @return $this
     */
    public function isRequired(): static;

    /**
     * @param array $fields
     * @return $this
     */
    public function isRequiredArray(array $fields): static;

    /**
     * @return $this
     */
    public function isEmail(): static;

    /**
     * @param $length int
     * @return $this
     */
    public function minLength(int $length): static;

    /**
     * @param $length
     * @return $this
     */
    public function maxLength($length): static;

    /**
     * @return $this
     */
    public function isArray(): static;

    /**
     * @param $condition
     * @return $this
     */
    public function isRequiredIf($condition): static;

    /**
     * @param $condition
     * @return $this
     */
    public function notExists($condition): static;

    /**
     * @return $this
     */
    public function isUrl(): static;

    /**
     * @return $this
     */
    public function isNumeric(): static;

    /**
     * Get rules
     *
     * @return array
     */
    public function rules(): array;
}

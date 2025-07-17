<?php
namespace App\Core\Interfaces;


interface ValidatorRulesInterface
{
    /**
     * Is there a value?
     *
     * @return $this
     * @uses Rules::set()->isRequired()
     */
    public function isRequired(): static;

    /**
     * Is value multidimensional array and each row's values are required
     *
     * @param array $fields
     * @return $this
     * @uses Rules::set()->isRequiredArray(['name', 'type'])
     */
    public function isRequiredArray(array $fields): static;

    /**
     * Is value a valid email address
     *
     * @return $this
     * @uses Rules::set()->isEmail()
     */
    public function isEmail(): static;

    /**
     * Is value's length at least n
     *
     * @param $length int
     * @return $this
     * @uses Rules::set()->minLength(2)
     */
    public function minLength(int $length): static;

    /**
     * Is value's length less than or equal to given number?
     *
     * @param $length
     * @return $this
     * @uses Rules::set()->maxLength(2)
     */
    public function maxLength($length): static;

    /**
     * Is array field
     *
     * @return $this
     * @uses Rules::set()->isArray()
     */
    public function isArray(): static;

    /**
     * Field will be required if it matches with condition
     *
     * @param $condition
     * @return $this
     * @uses Rules::set()->isRequiredIf('age:greater_than:10')
     * @uses Rules::set()->isRequiredIf('age:greater_than_equals_to:10')
     * @uses Rules::set()->isRequiredIf('age:equals:10')
     * @uses Rules::set()->isRequiredIf('age:less_than:10')
     * @uses Rules::set()->isRequiredIf('age:less_than_equals_to:10')
     */
    public function isRequiredIf($condition): static;

    /**
     * If a defined field's value doesn't exist in a supplied model
     *
     * @param $condition
     * @return $this
     * @uses ["email" => Rules::set()->exists(Model::class)]
     */
    public function notExists($condition): static;

    /**
     * Defined field's value should exist in a supplied model
     *
     * @param $condition
     * @param string $localKey
     * @return $this
     * @uses Rules::set()->exists(Model::class, 'id')
     */
    public function exists($condition, string $localKey = 'id'): static;

    /**
     * Is value a URL
     *
     * @return $this
     * @uses Rules::set()->isUrl()
     */
    public function isUrl(): static;

    /**
     * Is value a numeric
     *
     * @return $this
     * @uses Rules::set()->isNumeric()
     */
    public function isNumeric(): static;

    /**
     * Get rules
     *
     * @return array
     */
    public function rules(): array;
}

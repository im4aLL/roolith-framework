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
     * Is value multidimensional array and each row's values are required.
     *
     * @param array<int, string|int> $fields Required sub-field names.
     * @return static Self for chaining.
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
     * Is value's length at least n.
     *
     * @param int $length Minimum length.
     * @return static Self for chaining.
     * @uses Rules::set()->minLength(2)
     */
    public function minLength(int $length): static;

    /**
     * Is value's length less than or equal to given number?
     *
     * @param int $length Maximum length.
     * @return static Self for chaining.
     * @uses Rules::set()->maxLength(2)
     */
    public function maxLength(int $length): static;

    /**
     * Is array field
     *
     * @return $this
     * @uses Rules::set()->isArray()
     */
    public function isArray(): static;

    /**
     * Field will be required if it matches with condition.
     *
     * Condition is a `field:operator:value` string (split with limit 3 so
     * values may contain colons) or a `[field, operator, value]` struct.
     * Operators: equals, less_than, less_than_equals_to, greater_than,
     * greater_than_equals_to.
     *
     * @param string|array<int, mixed> $condition Condition string or 3-tuple struct.
     * @return static Self for chaining.
     * @uses Rules::set()->isRequiredIf('age:greater_than:10')
     * @uses Rules::set()->isRequiredIf('age:greater_than_equals_to:10')
     * @uses Rules::set()->isRequiredIf('age:equals:10')
     * @uses Rules::set()->isRequiredIf('age:less_than:10')
     * @uses Rules::set()->isRequiredIf('age:less_than_equals_to:10')
     */
    public function isRequiredIf(string|array $condition): static;

    /**
     * If a defined field's value doesn't exist in a supplied model.
     *
     * @param string|object $condition Model class name or instance.
     * @return static Self for chaining.
     * @uses ["email" => Rules::set()->exists(Model::class)]
     */
    public function notExists(string|object $condition): static;

    /**
     * Defined field's value should exist in a supplied model.
     *
     * @param string|object $condition Model class name or instance.
     * @param string $localKey Column/field to match.
     * @return static Self for chaining.
     * @uses Rules::set()->exists(Model::class, 'id')
     */
    public function exists(string|object $condition, string $localKey = 'id'): static;

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
     * Get rules.
     *
     * @return array<string, mixed> Rule name to rule value map.
     */
    public function rules(): array;
}

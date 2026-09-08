<?php
namespace App\Core;


use App\Core\Interfaces\ValidatorRulesInterface;

/**
 * Fluent builder for Validator rule maps.
 *
 * Each method records a Rules method name plus its rule value; Validator
 * dispatches via Rules::$method($inputs, $field, $value). Presence rules
 * (isRequired, isRequiredArray, isRequiredIf) run even when the key is
 * missing; all others are skipped for missing keys so optional fields
 * pass when absent.
 */
class ValidatorRules implements ValidatorRulesInterface
{
    /**
     * Recorded rules keyed by Rules method name.
     *
     * @var array<string, mixed>
     */
    protected array $rules;

    /**
     * Create an empty rule set.
     */
    public function __construct()
    {
        $this->rules = [];
    }

    /**
     * Get the recorded rule map.
     *
     * @return array<string, mixed> Rule name to rule value map.
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @inheritDoc
     */
    public function isRequired(): static
    {
        $this->rules['required'] = true;

        return $this;
    }

    /**
     * Is required array.
     *
     * @param array<int, string|int> $fields Required sub-field names.
     * @return static Self for chaining.
     */
    public function isRequiredArray(array $fields = []): static
    {
        $this->rules['requiredArray'] = $fields;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEmail(): static
    {
        $this->rules['email'] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function minLength(int $length): static
    {
        $this->rules['minLength'] = $length;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function maxLength(int $length): static
    {
        $this->rules['maxLength'] = $length;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isArray(): static
    {
        $this->rules['isArray'] = true;

        return $this;
    }

    /**
     * Mark a field required when another field meets a condition.
     *
     * Accepts a strict struct or a colon string. String form is
     * `field:operator:value` split with limit 3 so values may contain
     * colons (for example `time:equals:10:30` keeps `10:30` as the value).
     * Array form is `[field, operator, value]` and is used as-is. Operators
     * are documented on Rules::requiredIf(): equals, less_than,
     * less_than_equals_to, greater_than, greater_than_equals_to.
     *
     * @param string|array<int, mixed> $condition Condition string or 3-tuple struct.
     * @return static Self for chaining.
     */
    public function isRequiredIf(string|array $condition): static
    {
        if (is_array($condition)) {
            $this->rules['requiredIf'] = array_values($condition);

            return $this;
        }

        $this->rules['requiredIf'] = explode(':', $condition, 3);

        return $this;
    }

    /**
     * Value must not exist in the supplied model table.
     *
     * @param string|object $condition Model class name or instance for Rules::notExistsInTable().
     * @return static Self for chaining.
     */
    public function notExists(string|object $condition): static
    {
        $this->rules['notExistsInTable'] = $condition;

        return $this;
    }

    /**
     * Value must exist in the supplied model table.
     *
     * @param string|object $condition Model class name or instance for Rules::existsInTable().
     * @param string $localKey Column/field to match (for example id).
     * @return static Self for chaining.
     */
    public function exists(string|object $condition, string $localKey = 'id'): static
    {
        $this->rules['existsInTable'] = [
            'condition' => $condition,
            'localKey' => $localKey,
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isUrl(): static
    {
        $this->rules['url'] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isNumeric(): static
    {
        $this->rules['numeric'] = true;

        return $this;
    }
}

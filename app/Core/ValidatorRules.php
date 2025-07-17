<?php
namespace App\Core;


use App\Core\Interfaces\ValidatorRulesInterface;

class ValidatorRules implements ValidatorRulesInterface
{
    protected array $rules;

    public function __construct()
    {
        $this->rules = [];
    }

    /**
     * @inheritDoc
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
     * Is required array
     *
     * @param array $fields
     * @return $this
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
    public function maxLength($length): static
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
     * @inheritDoc
     */
    public function isRequiredIf($condition): static
    {
        $this->rules['requiredIf'] = explode(':', $condition);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function notExists($condition): static
    {
        $this->rules['notExistsInTable'] = $condition;

        return $this;
    }

    /**
     * If value exists in a table
     *
     * @param $condition
     * @param string $localKey
     * @return $this
     */
    public function exists($condition, string $localKey = 'id'): static
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

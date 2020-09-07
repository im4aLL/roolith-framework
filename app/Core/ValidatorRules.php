<?php
namespace App\Core;


use App\Core\Interfaces\ValidatorRulesInterface;

class ValidatorRules implements ValidatorRulesInterface
{
    protected $rules;

    public function __construct()
    {
        $this->rules = [];
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return $this->rules;
    }

    /**
     * @inheritDoc
     */
    public function isRequired()
    {
        $this->rules['required'] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEmail()
    {
        $this->rules['email'] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function minLength($length)
    {
        $this->rules['minLength'] = $length;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function maxLength($length)
    {
        $this->rules['maxLength'] = $length;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isArray()
    {
        $this->rules['isArray'] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isRequiredIf($condition)
    {
        $this->rules['requiredIf'] = explode(':', $condition);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function notExists($condition)
    {
        $this->rules['notExistsInTable'] = $condition;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isUrl()
    {
        $this->rules['url'] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isNumeric()
    {
        $this->rules['numeric'] = true;

        return $this;
    }
}
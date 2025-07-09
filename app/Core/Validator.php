<?php
namespace App\Core;

use App\Core\Interfaces\ValidatorInterface;
use App\Core\Interfaces\ValidatorRulesInterface;

/*
 * Usage
 * ===================================================
    $validator = new \App\Core\Validator();
    $validator->check(
        [
            'name' => 'john',
            'email' => 'me@habibhadi.com',
            'company' => '',
            'age' => 18,
            'url' => 'something!',
            'data' => [],
            'associative_array' => ["name" => "", "type" => ""],
        ],
        [
            'name' => Rules::set()->isRequired()->minLength(10)->maxLength(20)->notExists(\App\Models\User::class),
            'email' => Rules::set()->isEmail()->isRequired(),
            'company' => Rules::set()->isRequiredIf('age:greater_than:10'),
            'url' => Rules::set()->isUrl(),
            'age' => Rules::set()->isNumeric(),
            'data' => Rules::set()->isArray(),
            'associative_array' => Rules::set()->isRequiredArray(['name', 'type']),
        ]
    );
*/

class Validator implements ValidatorInterface
{
    protected array $errors;

    public function __construct()
    {
        $this->errors = [];
    }

    /**
     * Validate user input based on rules
     *
     * @param array $inputs
     * @param array $rules
     * @return $this
     */
    public function check(array $inputs, array $rules): static
    {
        foreach ($rules as $inputKey => $rulesInstance) {
            $ruleArray = $rulesInstance->rules();

            foreach ($ruleArray as $ruleItem => $ruleValue) {
                if (method_exists(Rules::class, $ruleItem)) {
                    $isValid = call_user_func([Rules::class, $ruleItem], $inputs, $inputKey, $ruleValue);

                    if (!$isValid) {
                        if (is_array($ruleValue)) {
                            $this->errors[$inputKey][] = [
                                $ruleItem,
                                $ruleValue
                            ];
                        } else {
                            $this->errors[$inputKey][] = $ruleItem;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function success(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * @inheritDoc
     */
    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * @inheritDoc
     */
    public function errors(): iterable
    {
        return $this->errors;
    }
}

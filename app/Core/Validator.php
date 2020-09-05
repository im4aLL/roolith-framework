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
        ],
        [
            'name' => Rules::set()->isRequired()->minLength(10)->isArray()->maxLength(20)->notExists(\App\Models\User::class),
            'email' => Rules::set()->isEmail()->isRequired(),
            'company' => Rules::set()->isRequiredIf('age:greater_than:10'),
            'url' => Rules::set()->isUrl(),
            'age' => Rules::set()->isNumeric(),
        ]
    );
*/

class Validator implements ValidatorInterface
{
    protected $errors;

    public function __construct()
    {
        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function check($inputs, $rules)
    {
        foreach ($inputs as $inputKey => $inputValue) {
            if (isset($rules[$inputKey])) {
                /* @var $rulesInstance ValidatorRulesInterface */
                $rulesInstance = $rules[$inputKey];
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
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function success()
    {
        return count($this->errors) === 0;
    }

    /**
     * @inheritDoc
     */
    public function fails()
    {
        return count($this->errors) > 0;
    }

    /**
     * @inheritDoc
     */
    public function errors()
    {
        return $this->errors;
    }
}
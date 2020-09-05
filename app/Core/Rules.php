<?php
namespace App\Core;

use App\Core\Interfaces\ValidatorRulesInterface;
use App\Models\Model;
use ReflectionClass;

class Rules
{
    /**
     * @return ValidatorRulesInterface
     */
    public static function set()
    {
        return new ValidatorRules();
    }

    /**
     * Get value
     *
     * @param $inputs
     * @param $name
     * @return mixed|null
     */
    protected static function getValue($inputs, $name)
    {
        return isset($inputs[$name]) ? $inputs[$name] : null;
    }

    /**
     * Required
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function required($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        if (empty($value)) {
            return false;
        }

        return strlen(trim($value)) > 0;
    }

    /**
     * Email
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function email($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Min length
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function minLength($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        return strlen($value) >= $ruleValue;
    }

    /**
     * Max length
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function maxLength($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        return strlen($value) <= $ruleValue;
    }

    /**
     * Is array
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function isArray($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        return is_array($value);
    }

    /**
     * Required if
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function requiredIf($inputs, $name, $ruleValue)
    {
        $anotherProperty = $ruleValue[0];
        $operator = $ruleValue[1];
        $anotherPropertyValue = $ruleValue[2];

        $isConditionValid = false;

        if (isset($inputs[$anotherProperty])) {
            if ($operator === 'equals') {
                $isConditionValid = $inputs[$anotherProperty] == $anotherPropertyValue;
            } else if ($operator === 'less_than') {
                $isConditionValid = $inputs[$anotherProperty] < $anotherPropertyValue;
            } else if ($operator === 'less_than_equals_to') {
                $isConditionValid = $inputs[$anotherProperty] <= $anotherPropertyValue;
            } else if ($operator === 'greater_than') {
                $isConditionValid = $inputs[$anotherProperty] > $anotherPropertyValue;
            } else if ($operator === 'greater_than_equals_to') {
                $isConditionValid = $inputs[$anotherProperty] >= $anotherPropertyValue;
            }
        }

        if ($isConditionValid) {
            return self::required($inputs, $name, $ruleValue);
        }

        return true;
    }

    /**
     * Same value not exists in table
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function notExistsInTable($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);
        try {
            $reflectionClass = new ReflectionClass($ruleValue);
        } catch (\ReflectionException $e) {
            $reflectionClass = null;
        }

        if (!$reflectionClass) {
            return false;
        }

        /* @var $instance Model */
        $instance = $reflectionClass->newInstance();

        $count = $instance::orm()->select([
            'field' => [$name]
        ])->where($name, $value, '=')->count();

        return $count === 0;
    }

    /**
     * If valid url
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function url($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Is numeric
     *
     * @param $inputs
     * @param $name
     * @param $ruleValue
     * @return bool
     */
    public static function numeric($inputs, $name, $ruleValue)
    {
        $value = self::getValue($inputs, $name);

        return is_numeric($value);
    }
}
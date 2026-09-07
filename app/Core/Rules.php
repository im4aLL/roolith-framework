<?php
namespace App\Core;

use App\Core\Interfaces\ValidatorRulesInterface;
use App\Models\Model;
use ReflectionClass;
use ReflectionException;

class Rules
{
    /**
     * @return ValidatorRulesInterface
     */
    public static function set(): ValidatorRulesInterface
    {
        return new ValidatorRules();
    }

    /**
     * Get value
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @return mixed|null
     */
    protected static function getValue(array $inputs, string $name): mixed
    {
        return $inputs[$name] ?? null;
    }

    /**
     * Required
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Rule value (unused, kept for validator signature).
     * @return bool
     */
    public static function required(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
    }

    /**
     * Required array
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Required sub-fields.
     * @return bool
     */
    public static function requiredArray(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        if (!is_countable($ruleValue) || count($ruleValue) == 0) {
            return is_countable($value) && count($value) > 0;
        }

        $isEveryFieldHasValue = true;

        for ($i = 0; $i < count($ruleValue); $i++) {
            $fieldData = $value[$ruleValue[$i]];

            for ($j = 0; $j < count($fieldData); $j++) {
                $isEveryFieldHasValue = !empty($fieldData[$j]) && strlen(trim($fieldData[$j])) > 0;

                if (!$isEveryFieldHasValue) {
                    break 2;
                }
            }

        }

        return $isEveryFieldHasValue;
    }

    /**
     * Email
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Rule value (unused, kept for validator signature).
     * @return bool
     */
    public static function email(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Min length
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Minimum length.
     * @return bool
     */
    public static function minLength(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        return strlen($value) >= $ruleValue;
    }

    /**
     * Max length
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Maximum length.
     * @return bool
     */
    public static function maxLength(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        return strlen($value) <= $ruleValue;
    }

    /**
     * Is array
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Rule value (unused, kept for validator signature).
     * @return bool
     */
    public static function isArray(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        return is_array($value);
    }

    /**
     * Required if
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Condition tuple [field, operator, value].
     * @return bool
     */
    public static function requiredIf(array $inputs, string $name, mixed $ruleValue): bool
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
     * The Same value doesn't exist in the table
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Model class name.
     * @return bool
     * @throws ReflectionException
     */
    public static function notExistsInTable(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);
        try {
            $reflectionClass = new ReflectionClass($ruleValue);
        } catch (ReflectionException $e) {
            $reflectionClass = null;
        }

        if (!$reflectionClass) {
            return false;
        }

        /* @var $instance Model */
        $instance = $reflectionClass->newInstance();

        $count = $instance::orm()->select([
            'field' => [$name]
        ])->where($name, '=', $value)->count();

        return $count === 0;
    }

    /**
     * Check if the value exists in the supplied model class and local key field
     *
     * @param array $inputs
     * @param string $name
     * @param array $ruleValue
     * @return bool
     * @throws ReflectionException
     */
    public static function existsInTable(array $inputs, string $name, array $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        try {
            $reflectionClass = new ReflectionClass($ruleValue['condition']);
        } catch (ReflectionException $e) {
            $reflectionClass = null;
        }

        if (!$reflectionClass) {
            return false;
        }

        /* @var $instance Model */
        $instance = $reflectionClass->newInstance();

        $count = $instance::orm()->select([
            'field' => [$ruleValue['localKey']]
        ])->where($ruleValue['localKey'], '=', $value)->count();

        return $count > 0;
    }

    /**
     * If valid url
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Rule value (unused, kept for validator signature).
     * @return bool
     */
    public static function url(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Is numeric
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Rule value (unused, kept for validator signature).
     * @return bool
     */
    public static function numeric(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        return is_numeric($value);
    }
}

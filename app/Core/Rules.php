<?php
namespace App\Core;

use App\Core\Interfaces\ValidatorRulesInterface;
use App\Models\Model;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Field validation rules used by Validator.
 *
 * Each method signature is (array $inputs, string $name, mixed $ruleValue)
 * so Validator can dispatch by rule name. Presence rules (required,
 * requiredArray, requiredIf) must run even when the key is missing;
 * Validator skips all other rules for missing keys to support optional
 * fields.
 */
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
     * Check that a field is present and non-empty.
     *
     * Explicit null and empty-string checks so falsy but present values
     * pass: "0", 0, 0.0, false, and [0] are present; null, "", whitespace-
     * only strings, and [] are missing. Strings are trimmed before the
     * empty check (is_scalar guard ensures trim/strlen never run on arrays
     * or objects); arrays pass when non-empty; any other scalar (int,
     * float, bool) passes; non-scalar objects pass as present (non-null).
     * A missing key reads as null via getValue() and therefore fails.
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Rule value (unused, kept for validator signature).
     * @return bool True when the value counts as present.
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

        if (is_scalar($value)) {
            return true;
        }

        return true;
    }

    /**
     * Check that an array field and its sub-fields are present.
     *
     * Guards: missing or non-array $value fails; empty $ruleValue (no
     * sub-fields) passes when $value is a non-empty array. Otherwise each
     * named sub-field must exist in $value and be present per required()
     * semantics ("0", 0, [0] pass; null, "", whitespace-only, [] fail).
     * List values (for example ['a','']) require every element to be
     * present; scalar values are checked directly. Never calls count() or
     * strlen() on null.
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name holding the array.
     * @param mixed $ruleValue Required sub-field names (array) or empty for any non-empty array.
     * @return bool True when the array and all named sub-fields are present.
     */
    public static function requiredArray(array $inputs, string $name, mixed $ruleValue): bool
    {
        if (!array_key_exists($name, $inputs)) {
            return false;
        }

        $value = $inputs[$name];

        if (!is_array($value) || count($value) === 0) {
            return false;
        }

        if (!is_array($ruleValue) || count($ruleValue) === 0) {
            return true;
        }

        foreach ($ruleValue as $field) {
            if (!is_string($field) && !is_int($field)) {
                return false;
            }

            if (!array_key_exists($field, $value)) {
                return false;
            }

            $fieldValue = $value[$field];

            if (is_array($fieldValue)) {
                if (count($fieldValue) === 0) {
                    return false;
                }

                foreach ($fieldValue as $element) {
                    if (!self::isPresentValue($element)) {
                        return false;
                    }
                }

                continue;
            }

            if (!self::isPresentValue($fieldValue)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check that a single value counts as present (shared by requiredArray).
     *
     * Mirrors required(): null fails; strings fail when empty or
     * whitespace-only; arrays fail when empty; any other scalar or object
     * counts as present (so "0", 0, [0] pass). Never calls strlen/trim on
     * non-strings.
     *
     * @param mixed $value Value to check.
     * @return bool True when the value counts as present.
     */
    private static function isPresentValue(mixed $value): bool
    {
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
     * Check that a value meets a minimum length (multibyte-safe).
     *
     * Guards: missing key, null, or array values fail without calling
     * strlen() on null. Scalars are cast to string then measured with
     * mb_strlen (fallback to strlen when mbstring is unavailable).
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Minimum length (int-like).
     * @return bool True when the value length is at least the minimum.
     */
    public static function minLength(array $inputs, string $name, mixed $ruleValue): bool
    {
        if (!array_key_exists($name, $inputs)) {
            return false;
        }

        $value = $inputs[$name];

        if ($value === null || is_array($value)) {
            return false;
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return false;
        }

        $minimum = (int) $ruleValue;

        return self::stringLength($value) >= $minimum;
    }

    /**
     * Check that a value stays within a maximum length (multibyte-safe).
     *
     * Guards: missing key, null, or array values fail without calling
     * strlen() on null. Scalars are cast to string then measured with
     * mb_strlen (fallback to strlen when mbstring is unavailable).
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param mixed $ruleValue Maximum length (int-like).
     * @return bool True when the value length is at most the maximum.
     */
    public static function maxLength(array $inputs, string $name, mixed $ruleValue): bool
    {
        if (!array_key_exists($name, $inputs)) {
            return false;
        }

        $value = $inputs[$name];

        if ($value === null || is_array($value)) {
            return false;
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return false;
        }

        $maximum = (int) $ruleValue;

        return self::stringLength($value) <= $maximum;
    }

    /**
     * Measure a scalar value length in characters (multibyte-safe).
     *
     * Casts to string first so ints, floats, and bools never reach
     * strlen() as null. Uses mb_strlen when available, otherwise strlen.
     *
     * @param mixed $value Scalar or stringable value to measure.
     * @return int Character length.
     */
    private static function stringLength(mixed $value): int
    {
        $string = (string) $value;

        if (function_exists('mb_strlen')) {
            return mb_strlen($string);
        }

        return strlen($string);
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
     * Require a field only when another field meets a condition.
     *
     * Condition struct is a 3-tuple [field, operator, value] (see
     * ValidatorRules::isRequiredIf()). Supported operators (documented
     * contract): `equals` (==), `less_than` (<), `less_than_equals_to`
     * (<=), `greater_than` (>), `greater_than_equals_to` (>=). Unknown
     * operators, malformed structs, or a missing condition field mean the
     * condition is not met, so the field is not required and this returns
     * true. When the condition is met, the field must satisfy required().
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name made required when the condition holds.
     * @param mixed $ruleValue Condition tuple [field, operator, value].
     * @return bool True when not required or when the required value is present.
     */
    public static function requiredIf(array $inputs, string $name, mixed $ruleValue): bool
    {
        if (!is_array($ruleValue) || count($ruleValue) !== 3) {
            return true;
        }

        $anotherProperty = $ruleValue[0];
        $operator = $ruleValue[1];
        $anotherPropertyValue = $ruleValue[2];

        if (!is_string($anotherProperty) && !is_int($anotherProperty)) {
            return true;
        }

        if (!is_string($operator)) {
            return true;
        }

        if (!array_key_exists($anotherProperty, $inputs)) {
            return true;
        }

        $actual = $inputs[$anotherProperty];
        $isConditionValid = false;

        if ($operator === 'equals') {
            $isConditionValid = $actual == $anotherPropertyValue;
        } else if ($operator === 'less_than') {
            $isConditionValid = $actual < $anotherPropertyValue;
        } else if ($operator === 'less_than_equals_to') {
            $isConditionValid = $actual <= $anotherPropertyValue;
        } else if ($operator === 'greater_than') {
            $isConditionValid = $actual > $anotherPropertyValue;
        } else if ($operator === 'greater_than_equals_to') {
            $isConditionValid = $actual >= $anotherPropertyValue;
        } else {
            return true;
        }

        if ($isConditionValid) {
            return self::required($inputs, $name, $ruleValue);
        }

        return true;
    }

    /**
     * The Same value doesn't exist in the table.
     *
     * Fails closed on an invalid model class by throwing (consistent with
     * Validator's loud failures) instead of returning false, so a typo in
     * the model name cannot silently pass or fail.
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param string|object $ruleValue Model class name or instance.
     * @return bool True when no row matches the value.
     * @throws InvalidArgumentException When the model class cannot be reflected, chained to the cause.
     */
    public static function notExistsInTable(array $inputs, string $name, mixed $ruleValue): bool
    {
        $value = self::getValue($inputs, $name);

        if (!is_string($ruleValue) && !is_object($ruleValue)) {
            throw new InvalidArgumentException(
                "Invalid model class for notExistsInTable on field '{$name}': expected class-string or object, got " . get_debug_type($ruleValue) . "."
            );
        }

        try {
            $reflectionClass = new ReflectionClass($ruleValue);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException(
                "Invalid model class for notExistsInTable on field '{$name}': cannot reflect '" . (is_string($ruleValue) ? $ruleValue : get_debug_type($ruleValue)) . "'.",
                0,
                $e
            );
        }

        /* @var $instance Model */
        $instance = $reflectionClass->newInstance();

        $count = $instance::orm()->select([
            'field' => [$name]
        ])->where($name, '=', $value)->count();

        return $count === 0;
    }

    /**
     * Check if the value exists in the supplied model class and local key field.
     *
     * Fails closed on an invalid model class or malformed struct by throwing
     * (consistent with Validator's loud failures) instead of returning
     * false.
     *
     * @param array<string, mixed> $inputs Input data.
     * @param string $name Field name.
     * @param array{condition: string|object, localKey: string} $ruleValue Struct with model class and local key.
     * @return bool True when at least one row matches the value.
     * @throws InvalidArgumentException When the struct is malformed or the model cannot be reflected, chained to the cause.
     */
    public static function existsInTable(array $inputs, string $name, array $ruleValue): bool
    {
        if (!array_key_exists('condition', $ruleValue) || (!is_string($ruleValue['condition']) && !is_object($ruleValue['condition']))) {
            throw new InvalidArgumentException(
                "Invalid rule struct for existsInTable on field '{$name}': 'condition' must be a model class-string or object."
            );
        }

        if (!array_key_exists('localKey', $ruleValue) || !is_string($ruleValue['localKey']) || trim($ruleValue['localKey']) === '') {
            throw new InvalidArgumentException(
                "Invalid rule struct for existsInTable on field '{$name}': 'localKey' must be a non-empty string."
            );
        }

        $value = self::getValue($inputs, $name);
        $condition = $ruleValue['condition'];
        $localKey = trim($ruleValue['localKey']);

        try {
            $reflectionClass = new ReflectionClass($condition);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException(
                "Invalid model class for existsInTable on field '{$name}': cannot reflect '" . (is_string($condition) ? $condition : get_debug_type($condition)) . "'.",
                0,
                $e
            );
        }

        /* @var $instance Model */
        $instance = $reflectionClass->newInstance();

        $count = $instance::orm()->select([
            'field' => [$localKey]
        ])->where($localKey, '=', $value)->count();

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

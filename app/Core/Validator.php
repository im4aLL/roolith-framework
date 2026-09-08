<?php
namespace App\Core;

use App\Core\Interfaces\ValidatorInterface;
use App\Core\Interfaces\ValidatorRulesInterface;
use InvalidArgumentException;

/*
 * Usage (string plus array split, see README Validator section)
 * ===================================================
    $validator = new \App\Core\Validator();
    $validator->check(
        [
            'name' => 'john doe long enough',
            'email' => 'me@habibhadi.com',
            'company' => '',
            'age' => 18,
            'url' => 'https://example.com',
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

/**
 * Input validator mapping fields to Rules checks.
 *
 * Usage
 * ===================================================
 * ```
 * $validator = new \App\Core\Validator();
 * $validator->check(
 *     ['name' => 'john'],
 *     ['name' => Rules::set()->isRequired()->minLength(2)]
 * );
 * ```
 *
 * Optional fields are supported: when an input key is missing, only
 * presence rules (required, requiredArray, requiredIf) are evaluated.
 * All other rules are skipped so a missing optional email/url/numeric
 * passes; add isRequired() when presence is mandatory.
 */
class Validator implements ValidatorInterface
{
    /**
     * Collected errors keyed by field name.
     *
     * @var array<string, mixed>
     */
    protected array $errors;

    /**
     * Create an empty validator.
     */
    public function __construct()
    {
        $this->errors = [];
    }

    /**
     * Rule names that assert presence and must run even when the key is missing.
     *
     * @var array<int, string>
     */
    private const PRESENCE_RULES = ['required', 'requiredArray', 'requiredIf'];

    /**
     * Validate user input based on rules.
     *
     * Behavior: unknown rule names throw InvalidArgumentException (typos
     * fail loudly instead of passing silently). A missing input key runs
     * only presence rules (required, requiredArray, requiredIf); all other
     * rules are skipped so optional fields may be absent. A missing key
     * with a `required` rule fails via Rules::required(null). Errors map
     * field name to the failing rule name(s).
     *
     * @param array<string, mixed> $inputs Input data keyed by field name.
     * @param array<string, ValidatorRulesInterface> $rules Rules per field.
     * @return static Self for chaining.
     * @throws InvalidArgumentException When a rule name has no matching Rules method.
     */
    public function check(array $inputs, array $rules): static
    {
        foreach ($rules as $inputKey => $rulesInstance) {
            $ruleArray = $rulesInstance->rules();
            $isMissing = !array_key_exists($inputKey, $inputs);

            foreach ($ruleArray as $ruleItem => $ruleValue) {
                if (!method_exists(Rules::class, $ruleItem)) {
                    throw new InvalidArgumentException(
                        "Unknown validation rule '{$ruleItem}' for field '{$inputKey}'. " .
                        "Did you mean one of: required, requiredArray, email, minLength, maxLength, isArray, requiredIf, notExistsInTable, existsInTable, url, numeric?"
                    );
                }

                if ($isMissing && !in_array($ruleItem, self::PRESENCE_RULES, true)) {
                    continue;
                }

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

        return $this;
    }

    /**
     * Whether validation passed with no errors.
     *
     * @return bool True when no errors were recorded.
     */
    public function success(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Whether validation failed with at least one error.
     *
     * @return bool True when errors were recorded.
     */
    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Get all validation errors keyed by field name.
     *
     * @return array<string, mixed> Errors grouped by field.
     */
    public function errors(): iterable
    {
        return $this->errors;
    }
}

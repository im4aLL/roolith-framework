<?php
namespace Tests;

use App\Core\Rules;
use App\Core\ValidatorRules;
use PHPUnit\Framework\TestCase;

/**
 * Covers hardened requiredArray, min/maxLength, and requiredIf.
 */
class RulesHardeningTest extends TestCase
{
    /**
     * requiredArray must pass for present sub-fields including zero-like values.
     *
     * @return void
     */
    public function testRequiredArrayPassesForPresentSubFields(): void
    {
        $inputs = ['data' => ['name' => '0', 'type' => [0]]];

        $this->assertTrue(Rules::requiredArray($inputs, 'data', ['name', 'type']));
    }

    /**
     * requiredArray must fail for missing, empty, or null sub-fields without fatal.
     *
     * @return void
     */
    public function testRequiredArrayFailsForMissingOrEmpty(): void
    {
        $this->assertFalse(Rules::requiredArray([], 'data', ['name']));
        $this->assertFalse(Rules::requiredArray(['data' => null], 'data', ['name']));
        $this->assertFalse(Rules::requiredArray(['data' => []], 'data', ['name']));
        $this->assertFalse(Rules::requiredArray(['data' => ['name' => '']], 'data', ['name']));
        $this->assertFalse(Rules::requiredArray(['data' => ['name' => null]], 'data', ['name']));
        $this->assertFalse(Rules::requiredArray(['data' => ['name' => 'ok']], 'data', ['missing']));
    }

    /**
     * requiredArray with no sub-fields must require a non-empty array.
     *
     * @return void
     */
    public function testRequiredArrayWithoutFieldsRequiresNonEmptyArray(): void
    {
        $this->assertTrue(Rules::requiredArray(['data' => ['a']], 'data', []));
        $this->assertFalse(Rules::requiredArray(['data' => []], 'data', []));
        $this->assertFalse(Rules::requiredArray(['data' => null], 'data', []));
    }

    /**
     * minLength and maxLength must use character length and guard null.
     *
     * @return void
     */
    public function testMinMaxLengthGuards(): void
    {
        $this->assertTrue(Rules::minLength(['f' => 'abcd'], 'f', 3));
        $this->assertFalse(Rules::minLength(['f' => 'ab'], 'f', 3));
        $this->assertFalse(Rules::minLength([], 'f', 3));
        $this->assertFalse(Rules::minLength(['f' => null], 'f', 3));
        $this->assertFalse(Rules::minLength(['f' => ['a']], 'f', 1));

        $this->assertTrue(Rules::maxLength(['f' => 'ab'], 'f', 3));
        $this->assertFalse(Rules::maxLength(['f' => 'abcd'], 'f', 3));
        $this->assertFalse(Rules::maxLength([], 'f', 3));
        $this->assertFalse(Rules::maxLength(['f' => null], 'f', 3));
    }

    /**
     * Length checks must be multibyte-safe.
     *
     * @return void
     */
    public function testLengthIsMultibyteSafe(): void
    {
        $this->assertTrue(Rules::minLength(['f' => 'éé'], 'f', 2));
        $this->assertTrue(Rules::maxLength(['f' => 'éé'], 'f', 2));
        $this->assertFalse(Rules::maxLength(['f' => 'ééé'], 'f', 2));
    }

    /**
     * requiredIf must keep colons inside the comparison value.
     *
     * @return void
     */
    public function testRequiredIfKeepsColonsInValue(): void
    {
        $rules = Rules::set()->isRequiredIf('time:equals:10:30')->rules();

        $this->assertSame(['time', 'equals', '10:30'], $rules['requiredIf']);

        $this->assertFalse(Rules::requiredIf(['time' => '10:30'], 'field', $rules['requiredIf']));
        $this->assertTrue(Rules::requiredIf(['time' => '10:30', 'field' => 'x'], 'field', $rules['requiredIf']));
        $this->assertTrue(Rules::requiredIf(['time' => 'other'], 'field', $rules['requiredIf']));
    }

    /**
     * requiredIf must accept a strict struct directly.
     *
     * @return void
     */
    public function testRequiredIfAcceptsStruct(): void
    {
        $rules = (new ValidatorRules())->isRequiredIf(['age', 'greater_than', 10])->rules();

        $this->assertSame(['age', 'greater_than', 10], $rules['requiredIf']);
        $this->assertFalse(Rules::requiredIf(['age' => 18], 'company', $rules['requiredIf']));
        $this->assertTrue(Rules::requiredIf(['age' => 5], 'company', $rules['requiredIf']));
    }

    /**
     * requiredIf with unknown operator or malformed struct must not require.
     *
     * @return void
     */
    public function testRequiredIfUnknownOperatorIsNotRequired(): void
    {
        $this->assertTrue(Rules::requiredIf(['age' => 18], 'company', ['age', 'nope', 10]));
        $this->assertTrue(Rules::requiredIf(['age' => 18], 'company', 'bad'));
        $this->assertTrue(Rules::requiredIf([], 'company', ['age', 'equals', 1]));
    }

    /**
     * notExistsInTable with an invalid model must throw with a chained cause.
     *
     * @return void
     */
    public function testNotExistsInTableThrowsOnInvalidModel(): void
    {
        try {
            Rules::notExistsInTable(['email' => 'a@b.com'], 'email', 'No\\Such\\Model');
            $this->fail('Expected InvalidArgumentException for invalid model.');
        } catch (\InvalidArgumentException $e) {
            $this->assertNotNull($e->getPrevious());
            $this->assertStringContainsString('notExistsInTable', $e->getMessage());
        }
    }

    /**
     * existsInTable with an invalid model must throw with a chained cause.
     *
     * @return void
     */
    public function testExistsInTableThrowsOnInvalidModel(): void
    {
        try {
            Rules::existsInTable(['id' => 1], 'id', ['condition' => 'No\\Such\\Model', 'localKey' => 'id']);
            $this->fail('Expected InvalidArgumentException for invalid model.');
        } catch (\InvalidArgumentException $e) {
            $this->assertNotNull($e->getPrevious());
            $this->assertStringContainsString('existsInTable', $e->getMessage());
        }
    }

    /**
     * existsInTable with a malformed struct must throw.
     *
     * @return void
     */
    public function testExistsInTableThrowsOnMalformedStruct(): void
    {
        try {
            Rules::existsInTable(['id' => 1], 'id', []);
            $this->fail('Expected InvalidArgumentException for malformed struct.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('existsInTable', $e->getMessage());
        }
    }
}

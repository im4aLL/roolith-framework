<?php
namespace Tests;

use App\Core\Rules;
use App\Core\Validator;
use App\Core\ValidatorRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Covers Validator loud failures and missing-field handling.
 */
class ValidatorTest extends TestCase
{
    /**
     * Unknown rule names must throw instead of passing silently.
     *
     * @return void
     */
    public function testUnknownRuleThrows(): void
    {
        $rules = new ValidatorRules();

        $ref = new \ReflectionClass($rules);
        $prop = $ref->getProperty('rules');
        $prop->setAccessible(true);
        $prop->setValue($rules, ['noSuchRule' => true]);

        $validator = new Validator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown validation rule/');

        $validator->check(['field' => 'value'], ['field' => $rules]);
    }

    /**
     * Missing field with required must fail.
     *
     * @return void
     */
    public function testMissingFieldWithRequiredFails(): void
    {
        $validator = new Validator();
        $validator->check([], ['name' => Rules::set()->isRequired()]);

        $this->assertTrue($validator->fails());
        $this->assertFalse($validator->success());
        $this->assertArrayHasKey('name', (array) $validator->errors());
    }

    /**
     * Missing optional field must pass (non-presence rules skipped).
     *
     * A missing key with only email/url/numeric/minLength and friends is
     * optional by design: Validator skips non-presence rules when the key
     * is absent so optional fields need no sentinel. Add isRequired() when
     * presence is mandatory.
     *
     * @return void
     */
    public function testMissingFieldWithoutRequiredEvaluatesRule(): void
    {
        $validator = new Validator();
        $validator->check([], ['email' => Rules::set()->isEmail()]);

        $this->assertTrue($validator->success());
        $this->assertFalse($validator->fails());
    }

    /**
     * Missing optional field with several non-presence rules must pass.
     *
     * @return void
     */
    public function testMissingOptionalFieldPasses(): void
    {
        $validator = new Validator();
        $validator->check(
            [],
            [
                'nickname' => Rules::set()->minLength(2)->maxLength(20),
                'website' => Rules::set()->isUrl(),
                'age' => Rules::set()->isNumeric(),
            ]
        );

        $this->assertTrue($validator->success());
        $this->assertFalse($validator->fails());
    }

    /**
     * Present but invalid optional field must still fail.
     *
     * Skipping applies only to missing keys; a provided bad email is
     * evaluated and recorded.
     *
     * @return void
     */
    public function testPresentInvalidOptionalFieldFails(): void
    {
        $validator = new Validator();
        $validator->check(['email' => 'not-an-email'], ['email' => Rules::set()->isEmail()]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', (array) $validator->errors());
    }

    /**
     * Missing field with requiredIf met must fail, unmet must pass.
     *
     * @return void
     */
    public function testMissingFieldWithRequiredIfRespectsCondition(): void
    {
        $failing = new Validator();
        $failing->check(['age' => 18], ['company' => Rules::set()->isRequiredIf('age:greater_than:10')]);

        $this->assertTrue($failing->fails());

        $passing = new Validator();
        $passing->check(['age' => 5], ['company' => Rules::set()->isRequiredIf('age:greater_than:10')]);

        $this->assertTrue($passing->success());
    }

    /**
     * Valid inputs must pass with no errors.
     *
     * @return void
     */
    public function testValidInputsPass(): void
    {
        $validator = new Validator();
        $validator->check(
            ['name' => '0', 'count' => 0],
            ['name' => Rules::set()->isRequired(), 'count' => Rules::set()->isRequired()]
        );

        $this->assertTrue($validator->success());
        $this->assertFalse($validator->fails());
    }
}

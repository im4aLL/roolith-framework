<?php
namespace Tests;

use App\Core\Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers Rules::required truthiness for zero-like values.
 */
class RulesTest extends TestCase
{
    /**
     * Provide required-field inputs and expected outcomes.
     *
     * @return array<string, array{0: array<string, mixed>, 1: bool}> Named cases with inputs and expected result.
     */
    public static function requiredProvider(): array
    {
        return [
            'string zero passes' => [['field' => '0'], true],
            'int zero passes' => [['field' => 0], true],
            'array with zero passes' => [['field' => [0]], true],
            'null fails' => [['field' => null], false],
            'empty string fails' => [['field' => ''], false],
            'empty array fails' => [['field' => []], false],
        ];
    }

    /**
     * Required must accept zero-like values and reject empty ones.
     *
     * @param array<string, mixed> $inputs Input data containing `field`.
     * @param bool $expected Expected validation result.
     * @return void
     */
    #[DataProvider('requiredProvider')]
    public function testRequired(array $inputs, bool $expected): void
    {
        $this->assertSame($expected, Rules::required($inputs, 'field', null));
    }
}

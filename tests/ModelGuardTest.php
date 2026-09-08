<?php
namespace Tests;

use App\Models\Model;
use PHPUnit\Framework\TestCase;

/**
 * Covers Model guards for misconfiguration.
 */
class ModelGuardTest extends TestCase
{
    /**
     * Empty table must throw with context instead of invalid SQL late.
     *
     * @return void
     */
    public function testEmptyTableThrows(): void
    {
        $model = new class extends Model {
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/table name is empty/');

        $model->getOrm();
    }

    /**
     * Static orm() on a misconfigured model must also throw.
     *
     * @return void
     */
    public function testStaticOrmOnEmptyTableThrows(): void
    {
        $modelClass = new class extends Model {
            /**
             * Expose table name for assertion.
             *
             * @return string Table name.
             */
            public static function exposedTableName(): string
            {
                return (new self())->getTableNameForTest();
            }

            /**
             * Read the table name.
             *
             * @return string Table name.
             */
            public function getTableNameForTest(): string
            {
                return $this->table;
            }
        };

        $this->assertSame('', $modelClass::exposedTableName());

        $this->expectException(\RuntimeException::class);

        $modelClass::orm();
    }
}

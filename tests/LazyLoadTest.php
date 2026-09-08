<?php
namespace Tests;

use App\Core\LazyLoad;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * LazyLoad relation helper: uninitialized-state guards, normalized string
 * key matching, invalid-model safety, generator preservation, and the keyed
 * index map replacing per-item filtering.
 */
class LazyLoadTest extends TestCase
{
    /**
     * LazyLoad must guard empty state and index by normalized string keys.
     *
     * @return void
     */
    public function testLazyLoadGuardsAndKeyedMap(): void
    {
        $rows = [(object) ['id' => 1], (object) ['id' => 2]];

        $loader = new LazyLoad($rows);

        $this->assertSame($rows, iterator_to_array($this->iterableToArray($loader->get())));

        $empty = new LazyLoad([]);
        $empty->with('App\\Models\\User', 'user_id', 'id');

        $this->assertSame([], iterator_to_array($this->iterableToArray($empty->get())));

        $missingKey = [(object) ['name' => 'noid']];
        $guarded = new LazyLoad($missingKey);
        $guarded->with('App\\Models\\User', 'user_id', 'id');

        // Missing foreign keys collect no ids and return early without warnings.
        // The attach path needs a DB, so assert the guard helper directly.
        $ref = new ReflectionClass(LazyLoad::class);
        $method = $ref->getMethod('indexByStringKey');
        $method->setAccessible(true);

        /** @var array<string, array<int, object>> $indexed */
        $indexed = $method->invoke(null, [(object) ['id' => 1], (object) ['id' => '1'], (object) ['id' => 2], (object) ['name' => 'x']], 'id');

        $this->assertArrayHasKey('1', $indexed);
        $this->assertCount(2, $indexed['1']);
        $this->assertArrayHasKey('2', $indexed);
        $this->assertArrayNotHasKey('', $indexed);
    }

    /**
     * Unknown model never throws and leaves rows intact.
     *
     * @return void
     */
    public function testLazyLoadUnknownModelDoesNotThrow(): void
    {
        $rows = [(object) ['id' => 1, 'user_id' => 1], (object) ['id' => 2, 'user_id' => 2]];

        $loader = new LazyLoad($rows);
        $loader->with('App\\DoesNotExist\\MissingModel', 'user_id', 'id');

        $result = $loader->get();
        $normalized = is_array($result) ? $result : iterator_to_array($result);

        $this->assertCount(2, $normalized);
    }

    /**
     * Non-model class never throws.
     *
     * @return void
     */
    public function testLazyLoadNonModelClassDoesNotThrow(): void
    {
        $rows = [(object) ['id' => 1, 'user_id' => 1]];

        $loader = new LazyLoad($rows);
        $loader->with(\stdClass::class, 'user_id', 'id');

        $result = $loader->get();
        $normalized = is_array($result) ? $result : iterator_to_array($result);

        $this->assertCount(1, $normalized);
    }

    /**
     * Generators are materialized once, first element survives.
     *
     * @return void
     */
    public function testLazyLoadPreservesGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield (object) ['id' => 1];
            yield (object) ['id' => 2];
        })();

        $loader = new LazyLoad($generator);

        $result = $loader->get();
        $normalized = is_array($result) ? $result : iterator_to_array($result);

        $this->assertCount(2, $normalized);
        $this->assertSame(1, $normalized[0]->id);
        $this->assertSame(2, $normalized[1]->id);
    }

    /**
     * Normalize any iterable to an array for assertions.
     *
     * @param iterable<int|string, mixed> $value Iterable to normalize.
     * @return array<int|string, mixed> Normalized array.
     */
    private function iterableToArray(iterable $value): array
    {
        return is_array($value) ? $value : iterator_to_array($value);
    }
}

<?php
namespace Tests;

use App\Utils\Arr;
use App\Utils\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the Arr BC alias and Collection falsy/callable paths.
 */
class ArrAndCollectionRegressionTest extends TestCase
{
    /**
     * The namespaced BC alias must still resolve to Arr.
     *
     * @return void
     */
    public function testBcAliasResolvesToArr(): void
    {
        $this->assertTrue(class_exists('App\Utils\_'));
        $this->assertTrue(is_a('App\Utils\_', 'App\Utils\Arr', true));
    }

    /**
     * Calls through the alias must delegate to Arr.
     *
     * @return void
     */
    public function testBcAliasDelegatesToArr(): void
    {
        $this->assertSame(Arr::join(['a', 'b'], ','), \App\Utils\_::join(['a', 'b'], ','));
    }

    /**
     * last() must preserve falsy values instead of mapping them to null.
     *
     * @return void
     */
    public function testLastPreservesFalsyValues(): void
    {
        $this->assertSame(0, Collection::make([0])->last());
        $this->assertSame('0', Collection::make(['0'])->last());
        $this->assertSame('', Collection::make([''])->last());
        $this->assertSame(false, Collection::make([false])->last());
        $this->assertSame([], Collection::make([[]])->last());
        $this->assertNull(Collection::make([])->last());
    }

    /**
     * sum() must support a callable without forwarding it to pluck(string).
     *
     * @return void
     */
    public function testSumWithCallable(): void
    {
        $this->assertSame(12, Collection::make([1, 2, 3])->sum(fn($x) => $x * 2));
        $this->assertSame(50, Collection::make([['age' => 30], ['age' => 20]])->sum('age'));
    }

    /**
     * avg() must support a callable through the sum() path.
     *
     * @return void
     */
    public function testAvgWithCallable(): void
    {
        $this->assertEquals(4, Collection::make([1, 2, 3])->avg(fn($x) => $x * 2));
        $this->assertNull(Collection::make([])->avg());
    }
}

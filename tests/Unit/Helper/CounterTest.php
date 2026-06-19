<?php

namespace Azuracom\ProcessBundle\Tests\Unit\Helper;

use Azuracom\ProcessBundle\Helper\Counter;
use PHPUnit\Framework\TestCase;

class CounterTest extends TestCase
{
    public function testIncrementAndGet(): void
    {
        $counter = new Counter();

        $this->assertSame(0, $counter->get('errors'));

        $counter->increment('errors');
        $counter->increment('errors');

        $this->assertSame(2, $counter->get('errors'));
        $this->assertSame(0, $counter->get('warnings'));
    }

    public function testDefaultType(): void
    {
        $counter = new Counter();
        $counter->increment();

        $this->assertSame(1, $counter->get());
    }

    public function testReset(): void
    {
        $counter = new Counter();
        $counter->increment('errors');
        $counter->reset();

        $this->assertSame(0, $counter->get('errors'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Calculator\Calculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Calculator::class)]
class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new Calculator();
    }

    public function testAdd(): void
    {
        self::assertSame(4.0, $this->calculator->add(2, 2));
        self::assertSame(0.0, $this->calculator->add(-1, 1));
    }

    public function testSubtract(): void
    {
        self::assertSame(3.0, $this->calculator->subtract(10, 7));
    }

    public function testMultiply(): void
    {
        self::assertSame(12.0, $this->calculator->multiply(3, 4));
    }

    // divide(), modulo(), power() intentionally not tested — partial coverage
}

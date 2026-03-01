<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MathTest extends TestCase
{
    public function testAddition(): void
    {
        self::assertSame(4, 2 + 2);
    }

    public function testSubtraction(): void
    {
        self::assertSame(3, 10 - 7);
    }
}

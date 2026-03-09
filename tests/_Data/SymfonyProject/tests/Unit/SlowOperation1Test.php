<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SlowOperation1Test extends TestCase
{
    public function testSlowOperation1(): void
    {
        sleep(1);
        self::assertTrue(true);
    }

    public function testSlowOperation2(): void
    {
        sleep(1);
        self::assertSame(42, 42);
    }

    public function testSlowOperation3(): void
    {
        sleep(1);
        self::assertNotEmpty('test');
    }

    public function testSlowOperation4(): void
    {
        sleep(1);
        self::assertIsString('hello');
    }
}

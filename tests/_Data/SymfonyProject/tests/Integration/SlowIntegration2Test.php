<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

class SlowIntegration2Test extends TestCase
{
    public function testExternalServiceCall1(): void
    {
        sleep(2);
        self::assertStringStartsWith('Hello', 'Hello World');
    }

    public function testExternalServiceCall2(): void
    {
        sleep(2);
        self::assertStringEndsWith('World', 'Hello World');
    }

    public function testExternalServiceCall3(): void
    {
        sleep(2);
        self::assertIsInt(42);
    }

    public function testExternalServiceCall4(): void
    {
        sleep(2);
        self::assertIsFloat(3.14);
    }

    public function testExternalServiceCall5(): void
    {
        sleep(2);
        self::assertIsBool(true);
    }
}

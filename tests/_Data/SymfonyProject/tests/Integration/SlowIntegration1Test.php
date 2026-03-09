<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

class SlowIntegration1Test extends TestCase
{
    public function testServiceIntegration1(): void
    {
        sleep(2);
        self::assertTrue(true);
    }

    public function testServiceIntegration2(): void
    {
        sleep(2);
        self::assertFalse(false);
    }

    public function testServiceIntegration3(): void
    {
        sleep(2);
        self::assertSame(['a', 'b'], ['a', 'b']);
    }

    public function testServiceIntegration4(): void
    {
        sleep(2);
        self::assertNotSame(1, '1');
    }

    public function testServiceIntegration5(): void
    {
        sleep(2);
        self::assertEmpty([]);
    }

    public function testServiceIntegration6(): void
    {
        sleep(2);
        self::assertIsArray([1, 2, 3]);
    }
}

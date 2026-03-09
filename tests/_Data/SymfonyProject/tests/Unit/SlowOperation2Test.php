<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SlowOperation2Test extends TestCase
{
    public function testDatabaseSimulation1(): void
    {
        sleep(1);
        self::assertCount(3, [1, 2, 3]);
    }

    public function testDatabaseSimulation2(): void
    {
        sleep(1);
        self::assertArrayHasKey('key', ['key' => 'value']);
    }

    public function testDatabaseSimulation3(): void
    {
        sleep(1);
        self::assertStringContainsString('world', 'hello world');
    }

    public function testDatabaseSimulation4(): void
    {
        sleep(1);
        self::assertGreaterThan(5, 10);
    }

    public function testDatabaseSimulation5(): void
    {
        sleep(1);
        self::assertLessThanOrEqual(100, 50);
    }
}

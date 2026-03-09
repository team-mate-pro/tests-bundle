<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SlowOperation3Test extends TestCase
{
    public function testApiCallSimulation1(): void
    {
        sleep(1);
        self::assertJson('{"name": "test"}');
    }

    public function testApiCallSimulation2(): void
    {
        sleep(1);
        self::assertMatchesRegularExpression('/^\d+$/', '12345');
    }

    public function testApiCallSimulation3(): void
    {
        sleep(1);
        self::assertInstanceOf(\stdClass::class, new \stdClass());
    }

    public function testApiCallSimulation4(): void
    {
        sleep(1);
        self::assertNull(null);
    }
}

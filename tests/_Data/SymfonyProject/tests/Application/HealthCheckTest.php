<?php

declare(strict_types=1);

namespace App\Tests\Application;

use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    public function testApplicationIsHealthy(): void
    {
        self::assertTrue(true);
    }

    public function testResponseFormat(): void
    {
        $expected = ['status' => 'ok'];
        $actual = ['status' => 'error'];

        self::assertSame($expected, $actual, 'Health check response should return ok status');
    }
}

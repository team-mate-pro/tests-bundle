<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testAppEnvIsSet(): void
    {
        self::assertNotEmpty(getenv('HOME'));
    }

    public function testPhpVersionMeetsRequirement(): void
    {
        self::assertTrue(version_compare(PHP_VERSION, '8.2.0', '>='));
    }
}

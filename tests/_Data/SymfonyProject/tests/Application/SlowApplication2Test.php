<?php

declare(strict_types=1);

namespace App\Tests\Application;

use PHPUnit\Framework\TestCase;

class SlowApplication2Test extends TestCase
{
    public function testEndToEndFlow1(): void
    {
        sleep(3);
        self::assertIsCallable(fn() => true);
    }

    public function testEndToEndFlow2(): void
    {
        sleep(3);
        self::assertIsIterable([1, 2, 3]);
    }

    public function testEndToEndFlow3(): void
    {
        sleep(3);
        self::assertIsNumeric('123');
    }

    public function testEndToEndFlow4(): void
    {
        sleep(3);
        self::assertIsScalar('string');
    }

    public function testEndToEndFlow5(): void
    {
        sleep(3);
        self::assertIsResource(fopen('php://memory', 'r'));
    }

    public function testEndToEndFlow6(): void
    {
        sleep(3);
        self::assertMatchesRegularExpression('/^[a-z]+$/', 'abcdef');
    }
}

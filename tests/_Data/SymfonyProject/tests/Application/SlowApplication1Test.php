<?php

declare(strict_types=1);

namespace App\Tests\Application;

use PHPUnit\Framework\TestCase;

class SlowApplication1Test extends TestCase
{
    public function testFullStackOperation1(): void
    {
        sleep(3);
        self::assertEqualsWithDelta(3.14, 3.14159, 0.01);
    }

    public function testFullStackOperation2(): void
    {
        sleep(3);
        self::assertObjectHasProperty('foo', (object) ['foo' => 'bar']);
    }

    public function testFullStackOperation3(): void
    {
        sleep(3);
        self::assertContains('apple', ['apple', 'banana', 'cherry']);
    }

    public function testFullStackOperation4(): void
    {
        sleep(3);
        self::assertNotContains('grape', ['apple', 'banana', 'cherry']);
    }

    public function testFullStackOperation5(): void
    {
        sleep(3);
        self::assertEqualsCanonicalizing([3, 2, 1], [1, 2, 3]);
    }

    public function testFullStackOperation6(): void
    {
        sleep(3);
        self::assertFileExists(__FILE__);
    }

    public function testFullStackOperation7(): void
    {
        sleep(3);
        self::assertDirectoryExists(__DIR__);
    }
}

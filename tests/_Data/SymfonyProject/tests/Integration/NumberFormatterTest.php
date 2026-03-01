<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Formatter\NumberFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NumberFormatter::class)]
class NumberFormatterTest extends TestCase
{
    private NumberFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new NumberFormatter();
    }

    public function testFormatCurrencyUsd(): void
    {
        self::assertSame('$1,234.56', $this->formatter->formatCurrency(1234.56));
    }

    public function testFormatCurrencyEur(): void
    {
        self::assertSame('1.234,56 €', $this->formatter->formatCurrency(1234.56, 'EUR'));
    }

    public function testFormatPercentage(): void
    {
        self::assertSame('75.0%', $this->formatter->formatPercentage(0.75));
    }

    // formatBytes(), formatCurrency GBP/default intentionally not tested — partial coverage
}

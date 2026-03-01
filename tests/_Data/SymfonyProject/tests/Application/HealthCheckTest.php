<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Calculator\Calculator;
use App\Formatter\NumberFormatter;
use App\Validator\EmailValidator;
use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    public function testCalculatorWorks(): void
    {
        $calculator = new Calculator();
        $result = $calculator->add(10, $calculator->multiply(2, 3));

        self::assertSame(16.0, $result);
    }

    public function testFormatterAndValidatorIntegration(): void
    {
        $validator = new EmailValidator();
        $formatter = new NumberFormatter();

        $emails = ['user@example.com', 'invalid', 'admin@test.org'];
        $validCount = count(array_filter($emails, [$validator, 'isValid']));

        $percentage = $formatter->formatPercentage($validCount / count($emails));

        self::assertSame('66.7%', $percentage);
    }
}

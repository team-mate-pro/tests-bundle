<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Validator\EmailValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailValidator::class)]
class EmailValidatorTest extends TestCase
{
    private EmailValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EmailValidator();
    }

    public function testValidEmail(): void
    {
        self::assertTrue($this->validator->isValid('user@example.com'));
    }

    public function testInvalidEmail(): void
    {
        self::assertFalse($this->validator->isValid('not-an-email'));
    }

    public function testGetDomain(): void
    {
        self::assertSame('example.com', $this->validator->getDomain('user@example.com'));
    }

    public function testGetDomainReturnsNullForInvalid(): void
    {
        self::assertNull($this->validator->getDomain('invalid'));
    }

    // isDisposable(), normalize() intentionally not tested — partial coverage
}

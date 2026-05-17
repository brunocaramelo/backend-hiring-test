<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Contact\Validators\ContactValidator;
use PHPUnit\Framework\TestCase;

final class ContactValidatorTest extends TestCase
{
    private ContactValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContactValidator();
    }

    private function dto(string $email): ContactDto
    {
        return new ContactDto(name: 'Test', email: $email, phone: '', company: '');
    }

    private function doValidate(string $email): void
    {
        $this->validator->validate($this->dto($email));
    }
     public function test_valid_email_passes(): void
    {
        $this->doValidate('alice@example.com');
        $this->assertTrue($this->validator->isValid());
    }

    public function test_empty_email_fails(): void
    {
        $this->doValidate('');
        $this->assertFalse($this->validator->isValid());
    }

    public function test_malformed_email_fails(): void
    {
        $this->doValidate('not-an-email');
        $this->assertFalse($this->validator->isValid());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidEmails')]
    public function test_various_invalid_formats_fail(string $email): void
    {
        $this->doValidate($email);
        $this->assertFalse($this->validator->isValid());
    }

    public static function invalidEmails(): array
    {
        return [
            'plain word'     => ['plainaddress'],
            'missing user'   => ['@missinguser.com'],
            'missing domain' => ['missingdomain@'],
            'double at'      => ['two@@at.com'],
            'spaces'         => ['spaces in@email.com'],
        ];
    }
    public function test_no_errors_for_valid_email(): void
    {
        $this->doValidate('valid@example.com');
        $this->assertEmpty($this->validator->getErrors());
    }


    public function test_missing_email_error_message(): void
    {
        $this->doValidate('');
        $this->assertStringContainsString('missing email', $this->validator->getErrors()[0] ?? '');
    }

    public function test_invalid_format_error_message(): void
    {
        $this->doValidate('bad-email');
        $this->assertStringContainsString('invalid email format', $this->validator->getErrors()[0] ?? '');
    }

    public function test_rejection_reason_for_empty_email(): void
    {
        $this->doValidate('');
        $this->assertSame('missing email', $this->validator->getErrors()[0] ?? '');
    }

    public function test_rejection_reason_for_invalid_format(): void
    {
        $this->doValidate('bad');
        $this->assertSame('invalid email format', $this->validator->getErrors()[0] ?? '');
    }

    
}

<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use PHPUnit\Framework\TestCase;

final class ContactDtoTest extends TestCase
{
    public function test_trims_all_fields_on_construction(): void
    {
        $dto = new ContactDto(
            name:    '  Alice  ',
            email:   '  alice@example.com  ',
            phone:   '  555-1234  ',
            company: '  Acme  ',
        );

        $this->assertSame('Alice',             $dto->name);
        $this->assertSame('alice@example.com', $dto->email);
        $this->assertSame('555-1234',          $dto->phone);
        $this->assertSame('Acme',              $dto->company);
    }

    public function test_empty_strings_stay_empty_after_trim(): void
    {
        $dto = new ContactDto(name: '', email: '', phone: '', company: '');

        $this->assertSame('', $dto->name);
        $this->assertSame('', $dto->email);
        $this->assertSame('', $dto->phone);
        $this->assertSame('', $dto->company);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $dto = new ContactDto(
            name:    'Bob',
            email:   'bob@example.com',
            phone:   '555-9999',
            company: 'Globex',
        );

        $this->assertSame([
            'name'    => 'Bob',
            'email'   => 'bob@example.com',
            'phone'   => '555-9999',
            'company' => 'Globex',
        ], $dto->toArray());
    }
}

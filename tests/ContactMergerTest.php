<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Contact\Mappers\ContactMerger;
use PHPUnit\Framework\TestCase;

final class ContactMergerTest extends TestCase
{
    private ContactMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new ContactMerger();
    }

    public function test_keeps_first_email_as_canonical(): void
    {
        $first  = new ContactDto(name: 'Alice', email: 'alice@example.com', phone: '', company: '');
        $second = new ContactDto(name: 'Alice', email: 'ALICE@EXAMPLE.COM', phone: '', company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('alice@example.com', $merged->email);
    }

    public function test_prefers_non_empty_name_from_first(): void
    {
        $first  = new ContactDto(name: 'Alice', email: 'a@b.com', phone: '', company: '');
        $second = new ContactDto(name: '',      email: 'a@b.com', phone: '', company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('Alice', $merged->name);
    }

    public function test_falls_back_to_second_name_when_first_is_empty(): void
    {
        $first  = new ContactDto(name: '',      email: 'a@b.com', phone: '', company: '');
        $second = new ContactDto(name: 'Alice', email: 'a@b.com', phone: '', company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('Alice', $merged->name);
    }

    public function test_prefers_non_empty_phone_from_first(): void
    {
        $first  = new ContactDto(name: 'A', email: 'a@b.com', phone: '555-0001', company: '');
        $second = new ContactDto(name: 'A', email: 'a@b.com', phone: '',         company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('555-0001', $merged->phone);
    }

    public function test_falls_back_to_second_phone_when_first_is_empty(): void
    {
        $first  = new ContactDto(name: 'A', email: 'a@b.com', phone: '',         company: '');
        $second = new ContactDto(name: 'A', email: 'a@b.com', phone: '555-0002', company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('555-0002', $merged->phone);
    }

    public function test_prefers_non_empty_company_from_first(): void
    {
        $first  = new ContactDto(name: 'A', email: 'a@b.com', phone: '', company: 'Acme');
        $second = new ContactDto(name: 'A', email: 'a@b.com', phone: '', company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('Acme', $merged->company);
    }

    public function test_falls_back_to_second_company_when_first_is_empty(): void
    {
        $first  = new ContactDto(name: 'A', email: 'a@b.com', phone: '', company: '');
        $second = new ContactDto(name: 'A', email: 'a@b.com', phone: '', company: 'Globex');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('Globex', $merged->company);
    }

    public function test_merge_produces_most_complete_record(): void
    {
        $first  = new ContactDto(name: 'Alice', email: 'alice@example.com', phone: '',         company: 'Acme');
        $second = new ContactDto(name: '',      email: 'alice@example.com', phone: '555-0001', company: '');

        $merged = $this->merger->merge($first, $second);

        $this->assertSame('Alice',             $merged->name);
        $this->assertSame('alice@example.com', $merged->email);
        $this->assertSame('555-0001',          $merged->phone);
        $this->assertSame('Acme',              $merged->company);
    }

    public function test_merge_with_whitespace_only_treats_as_empty(): void
    {
        $first  = new ContactDto(name: '   ', email: 'a@b.com', phone: '', company: '');
        $second = new ContactDto(name: 'Bob', email: 'a@b.com', phone: '', company: '');

        $merged = $this->merger->merge($first, $second);

        // ContactDto trims on construct, so '   ' becomes '' and second wins
        $this->assertSame('Bob', $merged->name);
    }
}

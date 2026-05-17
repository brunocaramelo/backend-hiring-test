<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Contact\Mappers\ContactMerger;
use BatchDataImporter\Contact\Services\ContactDuplicateRegistry;
use PHPUnit\Framework\TestCase;

final class ContactDuplicateRegistryTest extends TestCase
{
    private ContactDuplicateRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ContactDuplicateRegistry(new ContactMerger());
    }

    private function dto(string $email, string $name = 'Test', string $phone = '', string $company = ''): ContactDto
    {
        return new ContactDto(name: $name, email: $email, phone: $phone, company: $company);
    }

    public function test_adds_single_contact(): void
    {
        $this->registry->add($this->dto('alice@example.com', 'Alice'));

        $this->assertCount(1, $this->registry->all());
        $this->assertSame(0, $this->registry->getDuplicateCount());
    }

    public function test_deduplicates_by_email(): void
    {
        $this->registry->add($this->dto('alice@example.com', 'Alice'));
        $this->registry->add($this->dto('alice@example.com', 'Alice Duplicate'));

        $this->assertCount(1, $this->registry->all());
        $this->assertSame(1, $this->registry->getDuplicateCount());
    }

    public function test_merges_duplicate_keeping_most_complete_data(): void
    {
        $this->registry->add($this->dto('alice@example.com', 'Alice', '',         'Acme'));
        $this->registry->add($this->dto('alice@example.com', '',      '555-0001', ''));

        $contacts = $this->registry->all();
        $this->assertCount(1, $contacts);
        $this->assertSame('Alice',   $contacts[0]->name);
        $this->assertSame('555-0001', $contacts[0]->phone);
        $this->assertSame('Acme',    $contacts[0]->company);
    }

    public function test_distinct_emails_are_not_deduplicated(): void
    {
        $this->registry->add($this->dto('alice@example.com'));
        $this->registry->add($this->dto('bob@example.com'));

        $this->assertCount(2, $this->registry->all());
        $this->assertSame(0, $this->registry->getDuplicateCount());
    }

    public function test_counts_multiple_duplicates_correctly(): void
    {
        $this->registry->add($this->dto('alice@example.com'));
        $this->registry->add($this->dto('alice@example.com'));
        $this->registry->add($this->dto('alice@example.com'));

        $this->assertCount(1, $this->registry->all());
        $this->assertSame(2, $this->registry->getDuplicateCount());
    }

    public function test_all_returns_indexed_array(): void
    {
        $this->registry->add($this->dto('bob@example.com'));
        $this->registry->add($this->dto('alice@example.com'));

        $result = $this->registry->all();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
    }
}

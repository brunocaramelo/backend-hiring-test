<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Crm\CrmClientInterface;
use BatchDataImporter\Crm\CrmResponse;
use BatchDataImporter\Crm\CrmResponseStatus;
use BatchDataImporter\Pipeline\BatchProcessor;
use BatchDataImporter\Pipeline\RetryPolicy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class BatchProcessorTest extends TestCase
{
    private function makeContact(string $email): ContactDto
    {
        return new ContactDto(name: 'Test', email: $email, phone: '555', company: 'Co');
    }

    private function processorWithResponse(CrmResponseStatus $status, int $batchSize = 3): BatchProcessor
    {
        $client = $this->createMock(CrmClientInterface::class);
        $client->method('send')->willReturn(new CrmResponse($status, 'msg'));

        $retry = new RetryPolicy($client, maxAttempts: 1);
        return new BatchProcessor($retry, $batchSize);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_successful_contacts_go_to_imported(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::Success);
        $contacts  = [$this->makeContact('a@b.com'), $this->makeContact('c@d.com')];

        $result = $processor->process($contacts);

        $this->assertCount(2, $result['imported']);
        $this->assertCount(0, $result['failed']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_failed_contacts_go_to_failed(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::PermanentFailure);
        $contacts  = [$this->makeContact('a@b.com')];

        $result = $processor->process($contacts);

        $this->assertCount(0, $result['imported']);
        $this->assertCount(1, $result['failed']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_failed_record_includes_reason_and_status(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::PermanentFailure);
        $contacts  = [$this->makeContact('a@b.com')];

        $result = $processor->process($contacts);

        $this->assertArrayHasKey('reason',  $result['failed'][0]);
        $this->assertArrayHasKey('status',  $result['failed'][0]);
        $this->assertSame('PermanentFailure', $result['failed'][0]['status']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_imported_record_includes_attempts(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::Success);
        $contacts  = [$this->makeContact('a@b.com')];

        $result = $processor->process($contacts);

        $this->assertArrayHasKey('attempts', $result['imported'][0]);
        $this->assertSame(1, $result['imported'][0]['attempts']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_imported_record_includes_contact_fields(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::Success);
        $contacts  = [new ContactDto(name: 'Alice', email: 'alice@example.com', phone: '555', company: 'Acme')];

        $result = $processor->process($contacts);

        $record = $result['imported'][0];
        $this->assertSame('Alice',             $record['name']);
        $this->assertSame('alice@example.com', $record['email']);
        $this->assertSame('555',               $record['phone']);
        $this->assertSame('Acme',              $record['company']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_empty_contacts_returns_empty_results(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::Success);
        $result    = $processor->process([]);

        $this->assertCount(0, $result['imported']);
        $this->assertCount(0, $result['failed']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_processes_contacts_across_multiple_batches(): void
    {
        $processor = $this->processorWithResponse(CrmResponseStatus::Success, batchSize: 2);
        $contacts  = [
            $this->makeContact('a@b.com'),
            $this->makeContact('b@b.com'),
            $this->makeContact('c@b.com'),
        ];

        $result = $processor->process($contacts);

        $this->assertCount(3, $result['imported']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_mixed_success_and_failure(): void
    {
        $client = $this->createMock(CrmClientInterface::class);
        $client->method('send')->willReturnOnConsecutiveCalls(
            new CrmResponse(CrmResponseStatus::Success),
            new CrmResponse(CrmResponseStatus::PermanentFailure),
            new CrmResponse(CrmResponseStatus::Success),
        );

        $retry     = new RetryPolicy($client, maxAttempts: 1);
        $processor = new BatchProcessor($retry, 3);

        $contacts = [
            $this->makeContact('a@b.com'),
            $this->makeContact('b@b.com'),
            $this->makeContact('c@b.com'),
        ];

        $result = $processor->process($contacts);

        $this->assertCount(2, $result['imported']);
        $this->assertCount(1, $result['failed']);
    }
}

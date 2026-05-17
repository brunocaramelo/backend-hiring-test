<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Crm\CrmResponseStatus;
use BatchDataImporter\MockCrmClient;
use PHPUnit\Framework\TestCase;

final class MockCrmClientTest extends TestCase
{
    private MockCrmClient $client;

    protected function setUp(): void
    {
        $this->client = new MockCrmClient();
    }

    private function dto(string $email): ContactDto
    {
        return new ContactDto(name: 'Test', email: $email, phone: '', company: '');
    }

    public function test_returns_crm_response(): void
    {
        $response = $this->client->send($this->dto('alice@example.com'));

        $this->assertInstanceOf(\BatchDataImporter\Crm\CrmResponse::class, $response);
    }

    public function test_response_status_is_valid_enum_case(): void
    {
        $response = $this->client->send($this->dto('alice@example.com'));

        $this->assertInstanceOf(CrmResponseStatus::class, $response->status);
    }

    public function test_produces_all_four_response_types_over_many_calls(): void
    {
        $statuses = [];

        // Drive enough calls to hit all branches given deterministic formula
        for ($i = 0; $i < 200; $i++) {
            $email    = "user{$i}@example.com";
            $response = $this->client->send($this->dto($email));
            $statuses[$response->status->name] = true;
        }

        $this->assertArrayHasKey('Success',          $statuses, 'Expected at least one Success');
        $this->assertArrayHasKey('TemporaryFailure', $statuses, 'Expected at least one TemporaryFailure');
        $this->assertArrayHasKey('RateLimit',        $statuses, 'Expected at least one RateLimit');
        $this->assertArrayHasKey('PermanentFailure', $statuses, 'Expected at least one PermanentFailure');
    }

    public function test_same_email_produces_consistent_result_on_same_call_count(): void
    {
        $clientA = new MockCrmClient();
        $clientB = new MockCrmClient();

        $email     = 'fixed@example.com';
        $responseA = $clientA->send($this->dto($email));
        $responseB = $clientB->send($this->dto($email));

        // Same call count (1) + same email → same roll → same status
        $this->assertSame($responseA->status, $responseB->status);
    }
}

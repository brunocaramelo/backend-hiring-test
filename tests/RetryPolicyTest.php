<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Crm\CrmClientInterface;
use BatchDataImporter\Crm\CrmResponse;
use BatchDataImporter\Crm\CrmResponseStatus;
use BatchDataImporter\Pipeline\RetryPolicy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;


final class RetryPolicyTest extends TestCase
{
    private ContactDto $contact;

    protected function setUp(): void
    {
        $this->contact = new ContactDto(name: 'Alice', email: 'alice@example.com', phone: '', company: '');
    }

    private function stubClient(array $responses): CrmClientInterface
    {
        $client = $this->createMock(CrmClientInterface::class);
        $client->method('send')->willReturnOnConsecutiveCalls(...$responses);
        return $client;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_succeeds_on_first_attempt(): void
    {
        $client = $this->stubClient([
            new CrmResponse(CrmResponseStatus::Success, 'ok'),
        ]);

        $policy = new RetryPolicy($client, maxAttempts: 3);
        ['response' => $response, 'attempts' => $attempts] = $policy->send($this->contact);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(1, $attempts);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_retries_on_temporary_failure_and_eventually_succeeds(): void
    {
        $client = $this->stubClient([
            new CrmResponse(CrmResponseStatus::TemporaryFailure),
            new CrmResponse(CrmResponseStatus::Success),
        ]);

        $policy = new RetryPolicy($client, maxAttempts: 3);
        ['response' => $response, 'attempts' => $attempts] = $policy->send($this->contact);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(2, $attempts);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_retries_on_rate_limit_and_eventually_succeeds(): void
    {
        $client = $this->stubClient([
            new CrmResponse(CrmResponseStatus::RateLimit),
            new CrmResponse(CrmResponseStatus::Success),
        ]);

        $policy = new RetryPolicy($client, maxAttempts: 3);
        ['response' => $response, 'attempts' => $attempts] = $policy->send($this->contact);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(2, $attempts);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_does_not_retry_permanent_failure(): void
    {
        $client = $this->createMock(CrmClientInterface::class);
        $client->expects($this->once())
               ->method('send')
               ->willReturn(new CrmResponse(CrmResponseStatus::PermanentFailure));

        $policy = new RetryPolicy($client, maxAttempts: 3);
        ['response' => $response, 'attempts' => $attempts] = $policy->send($this->contact);

        $this->assertFalse($response->isSuccess());
        $this->assertSame(1, $attempts);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_exhausts_max_attempts_on_repeated_temporary_failure(): void
    {
        $client = $this->createMock(CrmClientInterface::class);
        $client->expects($this->exactly(3))
               ->method('send')
               ->willReturn(new CrmResponse(CrmResponseStatus::TemporaryFailure));

        $policy = new RetryPolicy($client, maxAttempts: 3);
        ['attempts' => $attempts] = $policy->send($this->contact);

        $this->assertSame(3, $attempts);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_max_attempts_one_makes_single_call(): void
    {
        $client = $this->createMock(CrmClientInterface::class);
        $client->expects($this->once())
               ->method('send')
               ->willReturn(new CrmResponse(CrmResponseStatus::TemporaryFailure));

        $policy = new RetryPolicy($client, maxAttempts: 1);
        ['attempts' => $attempts] = $policy->send($this->contact);

        $this->assertSame(1, $attempts);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_returns_last_response_after_exhausting_retries(): void
    {
        $client = $this->stubClient([
            new CrmResponse(CrmResponseStatus::TemporaryFailure, 'err1'),
            new CrmResponse(CrmResponseStatus::TemporaryFailure, 'err2'),
            new CrmResponse(CrmResponseStatus::TemporaryFailure, 'err3'),
        ]);

        $policy = new RetryPolicy($client, maxAttempts: 3);
        ['response' => $response] = $policy->send($this->contact);

        $this->assertSame(CrmResponseStatus::TemporaryFailure, $response->status);
        $this->assertSame('err3', $response->message);
    }
}

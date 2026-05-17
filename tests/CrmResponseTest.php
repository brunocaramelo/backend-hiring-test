<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Crm\CrmResponse;
use BatchDataImporter\Crm\CrmResponseStatus;
use PHPUnit\Framework\TestCase;

final class CrmResponseTest extends TestCase
{
    public function test_is_success_returns_true_for_success_status(): void
    {
        $response = new CrmResponse(CrmResponseStatus::Success);
        $this->assertTrue($response->isSuccess());
    }

    public function test_is_success_returns_false_for_temporary_failure(): void
    {
        $response = new CrmResponse(CrmResponseStatus::TemporaryFailure);
        $this->assertFalse($response->isSuccess());
    }

    public function test_is_success_returns_false_for_rate_limit(): void
    {
        $response = new CrmResponse(CrmResponseStatus::RateLimit);
        $this->assertFalse($response->isSuccess());
    }

    public function test_is_success_returns_false_for_permanent_failure(): void
    {
        $response = new CrmResponse(CrmResponseStatus::PermanentFailure);
        $this->assertFalse($response->isSuccess());
    }

    public function test_is_retryable_true_for_temporary_failure(): void
    {
        $response = new CrmResponse(CrmResponseStatus::TemporaryFailure);
        $this->assertTrue($response->isRetryable());
    }

    public function test_is_retryable_true_for_rate_limit(): void
    {
        $response = new CrmResponse(CrmResponseStatus::RateLimit);
        $this->assertTrue($response->isRetryable());
    }

    public function test_is_retryable_false_for_success(): void
    {
        $response = new CrmResponse(CrmResponseStatus::Success);
        $this->assertFalse($response->isRetryable());
    }

    public function test_is_retryable_false_for_permanent_failure(): void
    {
        $response = new CrmResponse(CrmResponseStatus::PermanentFailure);
        $this->assertFalse($response->isRetryable());
    }

    public function test_is_rate_limit_true(): void
    {
        $response = new CrmResponse(CrmResponseStatus::RateLimit);
        $this->assertTrue($response->isRateLimit());
    }

    public function test_is_rate_limit_false_for_other_statuses(): void
    {
        foreach ([
            CrmResponseStatus::Success,
            CrmResponseStatus::TemporaryFailure,
            CrmResponseStatus::PermanentFailure,
        ] as $status) {
            $this->assertFalse((new CrmResponse($status))->isRateLimit(), $status->name);
        }
    }

    public function test_message_stored_correctly(): void
    {
        $response = new CrmResponse(CrmResponseStatus::PermanentFailure, 'Contact already exists');
        $this->assertSame('Contact already exists', $response->message);
    }

    public function test_default_message_is_empty_string(): void
    {
        $response = new CrmResponse(CrmResponseStatus::Success);
        $this->assertSame('', $response->message);
    }
}

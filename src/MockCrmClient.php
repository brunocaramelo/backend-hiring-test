<?php

declare(strict_types=1);

namespace BatchDataImporter;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Crm\CrmClientInterface;
use BatchDataImporter\Crm\CrmResponse;
use BatchDataImporter\Crm\CrmResponseStatus;

/**
 * Simulates a third-party CRM with realistic failure modes.
 *
 * Weights (approximate):
 *  60% success
 *  15% temporary failure  → retryable
 *  15% rate limit         → retryable + back-off
 *  10% permanent failure  → do not retry
 */
final class MockCrmClient implements CrmClientInterface
{
    private int $callCount = 0;

    public function send(ContactDto $contact): CrmResponse
    {
        $this->callCount++;

        // Deterministic enough for tests but varied enough to exercise all paths
        $roll = ($this->callCount * 7 + crc32($contact->email)) % 100;

        return match (true) {
            $roll < 60 => new CrmResponse(CrmResponseStatus::Success,          'Contact imported'),
            $roll < 75 => new CrmResponse(CrmResponseStatus::TemporaryFailure, 'Service temporarily unavailable'),
            $roll < 90 => new CrmResponse(CrmResponseStatus::RateLimit,        'Too many requests — slow down'),
            default    => new CrmResponse(CrmResponseStatus::PermanentFailure,  'Contact already exists and cannot be updated'),
        };
    }
}

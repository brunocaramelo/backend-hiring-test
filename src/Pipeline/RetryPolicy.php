<?php

declare(strict_types=1);

namespace BatchDataImporter\Pipeline;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Crm\CrmClientInterface;
use BatchDataImporter\Crm\CrmResponse;
use BatchDataImporter\Crm\CrmResponseStatus;

/**
 * Wraps a CRM client with exponential back-off retry logic.
 *
 * Design decisions:
 * - Permanent failures are not retried (no point burning rate-limit budget).
 * - Rate-limit responses use a longer base delay to respect the upstream.
 * - Jitter avoids thundering-herd on batch retries.
 * - Max attempts is configurable so tests can set it to 1.
 */
final class RetryPolicy
{
    private const BASE_DELAY_MS      = 100;
    private const RATE_LIMIT_DELAY_MS = 500;
    private const JITTER_MAX_MS      = 50;

    public function __construct(
        private readonly CrmClientInterface $client,
        private readonly int $maxAttempts = 3,
    ) {}

    public function send(ContactDto $contact): array
    {
        $attempt  = 0;
        $response = null;

        while ($attempt < $this->maxAttempts) {
            $attempt++;
            $response = $this->client->send($contact);

            if ($response->isSuccess()) {
                break;
            }

            if (!$response->isRetryable()) {
                break;
            }

            if ($attempt < $this->maxAttempts) {
                $this->backOff($attempt, $response->isRateLimit());
            }
        }

        return ['response' => $response, 'attempts' => $attempt];
    }

    private function backOff(int $attempt, bool $isRateLimit): void
    {
        $base   = $isRateLimit ? self::RATE_LIMIT_DELAY_MS : self::BASE_DELAY_MS;
        $delay  = $base * (2 ** ($attempt - 1));
        $jitter = random_int(0, self::JITTER_MAX_MS);

        usleep(($delay + $jitter) * 1000);
    }
}

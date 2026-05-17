<?php

declare(strict_types=1);

namespace BatchDataImporter\Crm;

class CrmResponse
{
    public function __construct(
        public readonly CrmResponseStatus $status,
        public readonly string            $message = '',
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === CrmResponseStatus::Success;
    }

    public function isRetryable(): bool
    {
        return match ($this->status) {
            CrmResponseStatus::TemporaryFailure,
            CrmResponseStatus::RateLimit        => true,
            default                             => false,
        };
    }

    public function isRateLimit(): bool
    {
        return $this->status === CrmResponseStatus::RateLimit;
    }
}

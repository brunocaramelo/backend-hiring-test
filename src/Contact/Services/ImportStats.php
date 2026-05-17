<?php

declare(strict_types=1);

namespace BatchDataImporter\Contact\Services;

final class ImportStats
{
    private int $totalRecords = 0;
    private array $skipped = [];

    public function incrementTotal(): void
    {
        $this->totalRecords++;
    }

    public function addSkipped(array $raw, string $reason): void
    {
        $this->skipped[] = [
            'raw' => $raw,
            'reason' => $reason
        ];
    }

    public function compile(int $validCount, int $duplicateCount): array
    {
        return [
            'skipped' => $this->skipped,
            'stats' => [
                'total_records'     => $this->totalRecords,
                'valid_records'     => $validCount + $duplicateCount,
                'invalid_records'   => count($this->skipped),
                'duplicates_merged' => $duplicateCount,
            ],
        ];
    }
}
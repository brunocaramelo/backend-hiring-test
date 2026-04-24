<?php

declare(strict_types=1);

namespace RioSlum\HiringTest;

class ContactImporter
{
    public function __construct(
        private MockCrmClient $client,
        private int $batchSize = 3,
        private int $maxRetries = 2
    ) {
    }

    public function run(string $inputPath, string $outputPath): array
    {
        // TODO: Implement the import workflow.
        //
        // Suggested steps:
        // 1. Read contacts from $inputPath.
        // 2. Validate and normalize emails.
        // 3. Skip invalid contacts.
        // 4. Merge duplicates by email.
        // 5. Process contacts in batches.
        // 6. Send each contact to MockCrmClient.
        // 7. Retry temporary failures.
        // 8. Handle rate limit responses.
        // 9. Write a JSON report to $outputPath.

        $result = [
            'summary' => [
                'total_records' => 0,
                'valid_records' => 0,
                'invalid_records' => 0,
                'duplicates_merged' => 0,
                'attempted_imports' => 0,
                'successful_imports' => 0,
                'failed_imports' => 0,
            ],
            'imported' => [],
            'failed' => [],
            'skipped' => [],
        ];

        file_put_contents($outputPath, json_encode($result, JSON_PRETTY_PRINT));

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace BatchDataImporter;

use BatchDataImporter\Contact\Services\ContactFileConciliation;
use BatchDataImporter\Crm\CrmClientInterface;
use BatchDataImporter\Pipeline\BatchProcessor;
use BatchDataImporter\Pipeline\RetryPolicy;
use BatchDataImporter\Reader\JsonStreamReader;
use BatchDataImporter\Report\ImportReport;


final class ContactImporter
{
    private readonly BatchProcessor $processor;
    private readonly ImportReport   $reporter;
    private readonly JsonStreamReader $streamReader;

    public function __construct(
        CrmClientInterface $crmClient,
        int $batchSize,
        int $maxAttempts,
    ) {
        $retryPolicy       = new RetryPolicy($crmClient, $maxAttempts);
        
        $this->processor    = new BatchProcessor($retryPolicy, $batchSize);
        $this->reporter     = new ImportReport();
        $this->streamReader = new JsonStreamReader();
    }

    public function run(string $inputPath, string $outputPath): array
    {
        $conciliation = new ContactFileConciliation($this->streamReader, $inputPath);
        
        $normalized = $conciliation->handle();

        $results = $this->processor->process($normalized['contacts']);

        $report = $this->reporter->build(
            normalizationStats: $normalized['stats'],
            imported:           $results['imported'],
            failed:             $results['failed'],
            skipped:            $normalized['skipped'],
        );

        $this->reporter->write($report, $outputPath);

        return $report;
    }
}
<?php

declare(strict_types=1);

namespace BatchDataImporter\Pipeline;

use BatchDataImporter\Contact\Contact;
use BatchDataImporter\Crm\CrmResponseStatus;

final class BatchProcessor
{
    public function __construct(
        private readonly RetryPolicy $retryPolicy,
        private readonly int         $batchSize = 3,
    ) {}

    public function process(array $contacts): array
    {
        $imported = [];
        $failed   = [];

        foreach (array_chunk($contacts, $this->batchSize) as $batch) {
            foreach ($batch as $contact) {
                ['response' => $response, 'attempts' => $attempts] = $this->retryPolicy->send($contact);

                $record = array_merge($contact->toArray(), ['attempts' => $attempts]);

                if ($response->isSuccess()) {
                    $imported[] = $record;
                    continue;
                } 
                
                $failed[] = array_merge($record, [
                    'reason' => $response->message,
                    'status' => $response->status->name,
                ]);
                
            }
        }

        return compact('imported', 'failed');
    }
}

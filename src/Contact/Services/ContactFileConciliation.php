<?php

declare(strict_types=1);

namespace BatchDataImporter\Contact\Services;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Contact\Mappers\ContactMerger;
use BatchDataImporter\Contact\Services\ContactDuplicateRegistry;
use BatchDataImporter\Contact\Services\ImportStats;
use BatchDataImporter\Contact\Validators\ContactValidator;
use BatchDataImporter\Reader\JsonStreamReader;

final class ContactFileConciliation
{
    public function __construct(
        private readonly JsonStreamReader $fileReader, 
        private readonly string $path,
    ) {}

    public function handle(): array
    {
        $validator = new ContactValidator();
        $merger = new ContactMerger();
        $registry = new ContactDuplicateRegistry($merger);
        $stats = new ImportStats();

        $rawRows = $this->fileReader->read($this->path);

        foreach ($rawRows as $row) {

            $stats->incrementTotal();
            
            $rowData = (array) $row;
            
            $contact = new ContactDto(
                name:    $rowData['name'] ?? '',
                email:   $rowData['email'] ?? '',
                phone:   $rowData['phone'] ?? '',
                company: $rowData['company'] ?? ''
            );

            $validator->validate($contact);

            if (!$validator->isValid($contact)) {
                $stats->addSkipped($rowData, $validator->getErrors($contact)[0] ?? '');
                continue;
            }

            $registry->add($contact);
        }

        return array_merge([
                'contacts' => $registry->all()
            ],
            $stats->compile(count($registry->all()), $registry->getDuplicateCount())
        );
    }
}
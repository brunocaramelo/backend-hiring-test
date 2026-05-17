<?php

declare(strict_types=1);

namespace BatchDataImporter\Contact\Services;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Contact\Mappers\ContactMerger;

final class ContactDuplicateRegistry
{
    private array $index = [];

    private int $duplicateCount = 0;

    public function __construct(private readonly ContactMerger $merger) {}

    public function add(ContactDto $contact): void
    {
        $email = $contact->email;

        if (isset($this->index[$email])) {
            $this->index[$email] = $this->merger->merge($this->index[$email], $contact);
            $this->duplicateCount++;
            return;
        }

        $this->index[$email] = $contact;
    }

    public function all(): array
    {
        return array_values($this->index);
    }

    public function getDuplicateCount(): int
    {
        return $this->duplicateCount;
    }
}
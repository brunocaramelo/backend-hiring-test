<?php

declare(strict_types=1);

namespace BatchDataImporter\Contact\Mappers;

use BatchDataImporter\Contact\Dto\ContactDto;

final class ContactMerger
{
    public function merge(ContactDto $first, ContactDto $second): ContactDto
    {
        return new ContactDto(
            name:    $this->choicePrefer($first->name, $second->name),
            email:   $first->email,
            phone:   $this->choicePrefer($first->phone, $second->phone),
            company: $this->choicePrefer($first->company, $second->company),
        );
    }

    private function choicePrefer(string $first, string $second): string
    {
        return trim($first) !== '' ? $first : 
                                    $second;
    }
}
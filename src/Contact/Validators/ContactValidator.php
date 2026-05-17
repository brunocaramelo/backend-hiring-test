<?php

declare(strict_types=1);

namespace BatchDataImporter\Contact\Validators;

use BatchDataImporter\Contact\Dto\ContactDto;

final class ContactValidator
{
    private array $errors = [];

    public function validate(ContactDto $contact): void
    {
        $isValid = $this->setErrors($contact);
    }

    public function isValid(): bool
    {
        return !isset($this->errors[0]);
    }

    public function setErrors(ContactDto $contact): void
    {
        $this->errors = [];
   
        if (empty($contact->email)) {
            $this->errors[] = 'missing email';
        }

        elseif (filter_var($contact->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = 'invalid email format';
        }
    }

    public function getErrors() 
    {
        return $this->errors;
    }
}

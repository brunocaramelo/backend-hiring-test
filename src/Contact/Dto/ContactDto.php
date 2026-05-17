<?php

declare(strict_types=1);

namespace BatchDataImporter\Contact\Dto;

final class ContactDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $company,
    ) {
        $this->name    = trim($name);
        $this->email   = strtolower(trim($email));
        $this->phone   = trim($phone);
        $this->company = trim($company);
    }

    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone,
            'company' => $this->company,
        ];
    }
}

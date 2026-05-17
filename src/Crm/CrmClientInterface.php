<?php

declare(strict_types=1);

namespace BatchDataImporter\Crm;

use BatchDataImporter\Contact\Dto\ContactDto;
use BatchDataImporter\Crm\CrmResponse;

interface CrmClientInterface
{
    public function send(ContactDto $contact): CrmResponse;
}

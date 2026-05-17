<?php

declare(strict_types=1);

namespace BatchDataImporter\Crm;

enum CrmResponseStatus
{
    case Success;
    case TemporaryFailure;
    case RateLimit;
    case PermanentFailure;
}
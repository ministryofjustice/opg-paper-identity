<?php

declare(strict_types=1);

namespace Application\Enums;

enum IdRoute: string
{
    case KBV = "EXPERIAN";
    case POST_OFFICE = "POST_OFFICE";
    case VOUCHING = "VOUCHING";
    case COURT_OF_PROTECTION = "COURT_OF_PROTECTION";
}

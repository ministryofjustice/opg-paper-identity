<?php

declare(strict_types=1);

namespace Application\Sirius;

enum UpdateStatus: string
{
    case CopStarted = "COP_STARTED";
    case CounterServiceStarted = "COUNTER_SERVICE_STARTED";
    case Exit = "EXIT";
    case Failure = "FAILURE";
    case Success = "SUCCESS";
    case VouchStarted = "VOUCH_STARTED";
}

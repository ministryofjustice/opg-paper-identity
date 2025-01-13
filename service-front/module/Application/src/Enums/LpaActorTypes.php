<?php

declare(strict_types=1);

namespace Application\Enums;

enum LpaActorTypes: string
{
    case DONOR = "donor";
    case CP = "certificate provider";
    case ATTORNEY = "attorney";
    case R_ATTORNEY = "replacement attorney";
}

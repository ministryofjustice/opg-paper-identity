<?php

declare(strict_types=1);

namespace Application\Services\Contract;

interface NINOServiceInterface
{
    public function validateNINO(string $nino): string;

}

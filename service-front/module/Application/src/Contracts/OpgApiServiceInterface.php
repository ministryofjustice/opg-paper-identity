<?php

declare(strict_types=1);

namespace Application\Contracts;

interface OpgApiServiceInterface
{
    public function getIdOptionsData(): array;

    public function getDetailsData(): array;
}

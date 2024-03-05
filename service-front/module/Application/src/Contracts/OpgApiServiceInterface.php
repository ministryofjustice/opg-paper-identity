<?php

declare(strict_types=1);

namespace Application\Contracts;

interface OpgApiServiceInterface
{
    public function getIdOptionsData();

    public function getDetailsData();
}
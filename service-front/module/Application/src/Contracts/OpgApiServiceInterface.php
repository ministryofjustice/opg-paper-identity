<?php

declare(strict_types=1);

namespace Application\Contracts;

interface OpgApiServiceInterface
{
    public function makeApiRequest(string $uri): array;
    public function getIdOptionsData(): array;

    public function getDetailsData(): array;

    public function getAddressVerificationData(): array;
    public function getLpasByDonorData(): array;
}

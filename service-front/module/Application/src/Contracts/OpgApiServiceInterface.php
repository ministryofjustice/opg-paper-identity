<?php

declare(strict_types=1);

namespace Application\Contracts;

interface OpgApiServiceInterface
{
    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array;
    public function getIdOptionsData(): array;

    public function getDetailsData(): array;

    public function getAddressVerificationData(): array;
    public function getLpasByDonorData(): array;

    public function checkNinoValidity(string $nino): bool;

    public function checkDlnValidity(string $dln): bool;

    public function checkPassportValidity(string $passport): bool;

    public function getMortgageData(): array;
    public function getMobileData(): array;
    public function getInitialsElectoralRegister(): array;
    public function getCurrentAccountData(): array;
    public function getIdCheckQuestions(string $case): array;
    public function checkIdCheckAnswers(array $answers): bool;
}

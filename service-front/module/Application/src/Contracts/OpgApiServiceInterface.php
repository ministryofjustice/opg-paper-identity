<?php

declare(strict_types=1);

namespace Application\Contracts;

interface OpgApiServiceInterface
{
    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array;
    public function getDetailsData(): array;
    public function getAddressVerificationData(): array;
    public function getLpasByDonorData(): array;
    public function checkNinoValidity(string $nino): string;
    public function checkDlnValidity(string $dln): string;
    public function checkPassportValidity(string $passport): string;
    public function getIdCheckQuestions(string $uuid): array|bool;
    public function checkIdCheckAnswers(string $uuid, array $answers): bool;
    public function createCase(
        string $firstname,
        string $lastname,
        string $dob,
        string $personType,
        array $lpas
    ): array;
}

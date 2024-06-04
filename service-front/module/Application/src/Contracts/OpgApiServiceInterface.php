<?php

declare(strict_types=1);

namespace Application\Contracts;

interface OpgApiServiceInterface
{
    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array;
    public function getDetailsData(string $uuid): array;
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
        array $lpas,
        array $address,
    ): array;

    public function findLpa(string $uuid, string $lpa): array;

    public function updateIdMethod(string $uuid, string $method): array;

    public function listPostOfficesByPostcode(string $uuid, string $postcode): array;

    public function searchPostOfficesByLocation(
        string $uuid,
        string $location,
        int $page = 1
    ): array;

    public function getPostOfficeByCode(string $uuid, int $code): array;

    public function addSearchPostcode(string $uuid, string $postcode): array;
    public function addSelectedPostOffice(string $uuid, string $postOffice): array;
    public function confirmSelectedPostOffice(string $uuid, string $deadline): array;

    public function updateCaseWithLpa(string $uuid, string $lpa, bool $remove = false): array;

    public function searchAddressesByPostcode(string $uuid, string $postcode): array;

    public function addSelectedAltAddress(string $uuid, array $data): array;

    public function updateCaseSetDocumentComplete(string $uuid): array;
}

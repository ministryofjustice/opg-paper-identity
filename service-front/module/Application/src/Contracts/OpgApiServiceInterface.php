<?php

declare(strict_types=1);

namespace Application\Contracts;

/**
 * @psalm-type Question = array{
 *   externalId: string,
 *   question: string,
 *   prompts: string[],
 *   answered: bool
 * }
 */
interface OpgApiServiceInterface
{
    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array;
    public function getDetailsData(string $uuid): array;
    public function getAddressVerificationData(): array;
    public function getLpasByDonorData(): array;
    public function checkNinoValidity(string $nino): string;
    public function checkDlnValidity(string $dln): string;
    public function checkPassportValidity(string $passport): string;

    /**
     * @return Question[]|false
     */
    public function getIdCheckQuestions(string $uuid): array|bool;

    /**
     * @return array{complete: bool, passed: bool}
     */
    public function checkIdCheckAnswers(string $uuid, array $answers): array;
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

    public function listPostOfficesByPostcode(string $uuid, string $location): array;

    public function addSearchPostcode(string $uuid, string $postcode): array;
    public function addSelectedPostOffice(string $uuid, string $postOffice): array;
    public function confirmSelectedPostOffice(string $uuid, string $deadline): array;

    public function updateCaseWithLpa(string $uuid, string $lpa, bool $remove = false): array;

    public function addSelectedAltAddress(string $uuid, array $data): array;

    public function updateCaseSetDocumentComplete(string $uuid): array;

    public function updateCaseSetDob(string $uuid, string $dob): array;

    public function updateIdMethodWithCountry(string $uuid, array $data): array;

    public function updateCaseProgress(string $uuid, array $data): array;

    public function createYotiSession(string $uuid): array;

    public function estimatePostofficeDeadline(string $uuid): string;
}

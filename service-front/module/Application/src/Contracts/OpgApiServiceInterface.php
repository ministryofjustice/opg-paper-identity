<?php

declare(strict_types=1);

namespace Application\Contracts;

use Application\Enums\PersonType;

/**
 * @psalm-type Question = array{
 *   externalId: string,
 *   question: string,
 *   prompts: string[],
 *   answered: bool
 * }
 *
 * @psalm-type Address = array{
 *   line1: string,
 *   line2?: string,
 *   line3?: string,
 *   town?: string,
 *   postcode: string,
 *   country?: string,
 * }
 *
 * @psalm-type CaseData = array{
 *   lpas: string[],
 *   personType: PersonType,
 *   firstName: string,
 *   lastName: string,
 *   dob: string|null,
 *   address: Address,
 *   professionalAddress?: Address,
 *   identityCheckPassed?: bool|null,
 *   idMethod?: array{
 *     idCountry?: string,
 *     docType?: string,
 *     idRoute?: string,
 *   },
 *   counterService?: array{
 *     selectedPostOffice: string,
 *     notificationState: string,
 *     notificationsAuthToken: string,
 *     state: string,
 *     result: bool,
 *   },
 *   caseProgress?: array{
 *       abandonedFlow?: array{
 *           last_page?: string,
 *           timestamp?: string
 *       },
 *       docCheck?: array{
 *           idDocument: string,
 *           state: string
 *       },
 *       kbvs?: array {
 *           result: bool
 *       },
 *       fraudScore?: array {
 *           decision: string,
 *           score: int
 *       }
 *   },
 *   vouchingFor?: array{
 *     firstName: string,
 *     lastName: string},
 * }
 */
interface OpgApiServiceInterface
{
    public function healthCheck(): bool;

    /**
     * @return CaseData
     */
    public function getDetailsData(string $uuid): array;

    public function checkNinoValidity(string $uuid, string $nino): string;

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

    /**
     * @return array{uuid: string}
     */
    public function createCase(
        string $firstname,
        string $lastname,
        string|null $dob,
        PersonType $personType,
        array $lpas,
        array $address,
    ): array;

    public function updateCaseAddress(string $uuid, array $address): void;

    /**
     * @return array<string, array{
     *   name: string,
     *   address: string,
     *   post_code: string,
     * }>
     */
    public function listPostOfficesByPostcode(string $uuid, string $location): array;

    public function addSelectedPostOffice(string $uuid, string $postOffice): void;

    public function updateCaseWithLpa(string $uuid, string $lpa, bool $remove = false): void;

    /**
     * @param Address $data
     */
    public function addSelectedAddress(string $uuid, array $data): void;

    /**
     * @param Address $data
     */
    public function updateCaseProfessionalAddress(string $uuid, array $data): void;

    public function updateCaseSetDocumentComplete(string $uuid, string $idDocument, bool $state = true): void;

    public function updateCaseSetDob(string $uuid, string|null $dob): void;

    public function updateCaseSetName(string $uuid, string $firstName, string $lastName): void;

    /**
     * @param array{
     *   idCountry?: string,
     *   docType?: string,
     *   idRoute?: string,
     * } $data
     */
    public function updateIdMethod(string $uuid, array $data): void;

    /**
     * @param array{
     *   abandonedFlow?: array{
     *        last_page?: string,
     *        timestamp?: string
     *   },
     *   docCheck?: array{
     *        idDocument: string,
     *        state: string
     *   },
     *   kbvs?: array {
     *        result: bool
     *   },
     *   fraudScore?: array {
     *        decision: string,
     *        score: int
     *   }
     * } $data
     */
    public function updateCaseProgress(string $uuid, array $data): void;

    /**
     * @return array{pdfBase64: string}
     */
    public function createYotiSession(string $uuid): array;

    public function estimatePostofficeDeadline(string $uuid): string;

    public function getRouteAvailability(string $uuid = null): array;

    /**
     * @return array{decision: string}
     */
    public function requestFraudCheck(string $uuid): array;

    /**
     * @param string $uuid
     * @param string $assistance
     * @param string|null $details
     * @return void
     */
    public function updateCaseAssistance(string $uuid, string $assistance, string $details = null): void;

    public function sendIdentityCheck(string $uuid): void;
}

<?php

declare(strict_types=1);

namespace Application\Contracts;

use Application\Helpers\DependencyCheck;

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
 *   personType: "donor"|"certificateProvider",
 *   dob: string,
 *   address: Address,
 *   alternateAddress?: Address,
 *   idMethodIncludingNation?: array{
 *     id_country?: string,
 *     id_method?: string,
 *     id_route?: string,
 *   },
 *   searchPostcode?: string,
 *   counterService?: array{
 *     selectedPostOffice: string,
 *     notificationState: string,
 *     notificationsAuthToken: string,
 *     state: string,
 *     result: bool,
 *   },
 * }
 */
interface OpgApiServiceInterface
{
    public function healthCheck(): bool;

    /**
     * @return CaseData
     */
    public function getDetailsData(string $uuid): array;

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

    /**
     * @return array{uuid: string}
     */
    public function createCase(
        string $firstname,
        string $lastname,
        string $dob,
        string $personType,
        array $lpas,
        array $address,
    ): array;

    public function updateIdMethod(string $uuid, string $method): void;

    /**
     * @return array<string, array{
     *   name: string,
     *   address: string,
     *   post_code: string,
     * }>
     */
    public function listPostOfficesByPostcode(string $uuid, string $location): array;

    public function addSelectedPostOffice(string $uuid, string $postOffice): void;

    public function confirmSelectedPostOffice(string $uuid, string $deadline): void;

    public function updateCaseWithLpa(string $uuid, string $lpa, bool $remove = false): void;

    /**
     * @param Address $data
     */
    public function addSelectedAltAddress(string $uuid, array $data): void;

    public function updateCaseSetDocumentComplete(string $uuid): void;

    public function updateCaseSetDob(string $uuid, string $dob): void;

    /**
     * @param array{
     *   id_country?: string,
     *   id_method?: string,
     *   id_route?: string,
     * } $data
     */
    public function updateIdMethodWithCountry(string $uuid, array $data): void;

    /**
     * @param array{
     *   last_page: string,
     *   timestamp: string,
     * } $data
     */
    public function updateCaseProgress(string $uuid, array $data): void;

    /**
     * @return array{pdfBase64: string}
     */
    public function createYotiSession(string $uuid): array;

    public function estimatePostofficeDeadline(string $uuid): string;

    /**
     * @return DependencyCheck
     */
    public function getServiceAvailability(string $uuid = null): DependencyCheck;

    /**
     * @return array{decision: string}
     */
    public function requestFraudCheck(string $uuid): array;
}

<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\LpaTypes;
use Application\Enums\PersonType;
use Application\Exceptions\HttpException;
use Application\Exceptions\LpaNotFoundException;
use Application\Services\SiriusApiService;
use DateTime;
use Laminas\Http\Request;
use Laminas\Stdlib\RequestInterface;

/**
 * @psalm-import-type Lpa from SiriusApiService
 */
class SiriusDataProcessorHelper
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService
    ) {
    }

    /**
     * @param PersonType $personType
     * @param array $lpasQuery
     * @param Lpa $lpaData
     * @return array
     * @throws HttpException
     */
    public function createPaperIdCase(PersonType $personType, array $lpasQuery, array $lpaData): array
    {
        $processedData = $this->processLpaResponse($personType, $lpaData);

        return $this->opgApiService->createCase(
            $processedData['first_name'],
            $processedData['last_name'],
            $processedData['dob'],
            $personType,
            $lpasQuery,
            $processedData['address']
        );
    }

    /**
     * @param string $uuid
     * @param Request $request
     * @return void
     * @throws HttpException
     * @throws \DateMalformedStringException
     */
    public function updatePaperIdCaseFromSirius(string $uuid, Request $request)
    {
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $lpaUid = $detailsData['lpas'][0];
        $lpaData = $this->siriusApiService->getLpaByUid($lpaUid, $request);
        if (is_null($lpaData)) {
            throw new LpaNotFoundException("LPA not found: {$lpaUid}");
        }
        $processedData = $this->processLpaResponse($detailsData['personType'], $lpaData);

        if (
            $processedData['first_name'] !== $detailsData['firstName'] ||
            $processedData['last_name'] !== $detailsData['lastName']
        ) {
                $this->opgApiService->updateCaseSetName(
                    $uuid,
                    $processedData['first_name'],
                    $processedData['last_name']
                );
        }
        if ($processedData['dob'] !== $detailsData['dob']) {
            $this->opgApiService->updateCaseSetDob($uuid, $processedData['dob']);
        }
        if ($processedData['address'] !== $detailsData['address']) {
            $this->opgApiService->updateCaseAddress($uuid, $processedData['address']);
        }
    }

    /**
     * @param PersonType $personType
     * @param array $data
     * @return array{
     *   first_name: string,
     *   last_name: string,
     *   dob: string|null,
     *   address: array{
     *     line1: string,
     *     line2: string,
     *     line3: string,
     *     town: string,
     *     postcode: string,
     *     country: string
     *   }
     * }
     * @throws HttpException
     * @throws \DateMalformedStringException
     */
    public function processLpaResponse(PersonType $personType, array $data): array
    {
        if (in_array($personType, [PersonType::Donor, PersonType::Voucher])) {
            if (! empty($data['opg.poas.lpastore'])) {
                $address = (new AddressProcessorHelper())->processAddress(
                    $data['opg.poas.lpastore']['donor']['address'],
                    'lpaStoreAddressType'
                );

                return [
                    'first_name' => $data['opg.poas.lpastore']['donor']['firstNames'],
                    'last_name' => $data['opg.poas.lpastore']['donor']['lastName'],
                    'dob' => (new DateTime($data['opg.poas.lpastore']['donor']['dateOfBirth']))->format("Y-m-d"),
                    'address' => $address,
                ];
            }

            $address = (new AddressProcessorHelper())->processAddress(
                $data['opg.poas.sirius']['donor'],
                'siriusAddressType'
            );

            return [
                'first_name' => $data['opg.poas.sirius']['donor']['firstname'],
                'last_name' => $data['opg.poas.sirius']['donor']['surname'],
                'dob' => DateTime::createFromFormat(
                    'd/m/Y',
                    $data['opg.poas.sirius']['donor']['dob']
                )->format("Y-m-d"),
                'address' => $address,
            ];
        } elseif ($personType === PersonType::CertificateProvider) {
            if ($data['opg.poas.lpastore'] === null) {
                throw new HttpException(
                    400,
                    'ID check has status: draft and cannot be started',
                );
            }

            $address = (new AddressProcessorHelper())->processAddress(
                $data['opg.poas.lpastore']['certificateProvider']['address'],
                'lpaStoreAddressType'
            );

            return [
                'first_name' => $data['opg.poas.lpastore']['certificateProvider']['firstNames'],
                'last_name' => $data['opg.poas.lpastore']['certificateProvider']['lastName'],
                'dob' => null,
                'address' => $address,
            ];
        }

        throw new HttpException(400, 'Person type "' . $personType->value . '" is not valid');
    }

    public function createLpaDetailsArray(
        array $detailsData,
        Request|RequestInterface $request
    ): array {
        $lpaDetails = [];

        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $request);

            if (is_null($lpasData)) {
                continue;
            }

            if (empty($lpasData['opg.poas.lpastore'])) {
                $name = $lpasData['opg.poas.sirius']['donor']['firstname'] . " " .
                    $lpasData['opg.poas.sirius']['donor']['surname'];
                $type = LpaTypes::fromName($lpasData['opg.poas.sirius']['caseSubtype']);
            } else {
                $name = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                    $lpasData['opg.poas.lpastore']['donor']['lastName'];
                $type = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);
            }

            $lpaDetails[$lpa] = [
                'name' => $name,
                'type' => $type
            ];
        }
        return $lpaDetails;
    }
}

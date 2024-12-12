<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\HttpException;
use Application\Services\SiriusApiService;
use DateTime;
use Laminas\Http\Request;

/**
 * @psalm-type Address = array{
 *  line1: string,
 *  line2?: string,
 *  line3?: string,
 *  town?: string,
 *  postcode?: string,
 *  country: string,
 * }
 *
 * @psalm-type Attorney = array{
 *   firstNames: string,
 *   lastName: string,
 *   dateOfBirth: string,
 * }
 *
 * @psalm-type Lpa = array{
 *  "opg.poas.sirius": array{
 *    id: int,
 *    caseSubtype: string,
 *    donor: array{
 *      firstname: string,
 *      surname: string,
 *      dob: string,
 *      addressLine1: string,
 *      addressLine2?: string,
 *      addressLine3?: string,
 *      town?: string,
 *      postcode?: string,
 *      country: string,
 *    },
 *  },
 *  "opg.poas.lpastore": ?array{
 *    lpaType: string,
 *    donor: array{
 *      firstNames: string,
 *      lastName: string,
 *      dateOfBirth: string,
 *      address: Address,
 *    },
 *    certificateProvider: array{
 *      firstNames: string,
 *      lastName: string,
 *      dateOfBirth: string,
 *      address: Address,
 *    },
 *    attorneys: Attorney[],
 *  },
 * }
 */
class SiriusDataProcessorHelper
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService
    ) {
    }

    /**
     * @param array $lpasQuery
     * @param Lpa $lpaData
     * @return array
     * @throws HttpException
     */
    public function createPaperIdCase(string $type, array $lpasQuery, array $lpaData): array
    {
        $processedData = $this->processLpaResponse($type, $lpaData);

        return $this->opgApiService->createCase(
            $processedData['first_name'],
            $processedData['last_name'],
            $processedData['dob'],
            $type,
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
        $processedData = $this->processLpaResponse($detailsData['personType'], $lpaData);

        $this->opgApiService->updateCaseSetName(
            $uuid,
            $processedData['first_name'],
            $processedData['last_name']
        );
        $this->opgApiService->updateCaseSetDob($uuid, $processedData['dob']);
        $this->opgApiService->updateCaseAddress($uuid, $processedData['address']);
    }

    /**
     * @param string $type
     * @param array $data
     * @return array{
     *   first_name: string,
     *   last_name: string,
     *   dob: DateTime,
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
    public function processLpaResponse(string $type, array $data): array
    {
        if (in_array($type, ['donor', 'voucher'])) {
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
                'dob' => DateTime::createFromFormat('d/m/Y', $data['opg.poas.sirius']['donor']['dob'])->format("Y-m-d"),
                'address' => $address,
            ];
        } elseif ($type === 'certificateProvider') {
            if ($data['opg.poas.lpastore'] === null) {
                throw new HttpException(
                    400,
                    'Cannot ID check this certificate provider as the LPA has not yet been submitted',
                );
            }

            $address = (new AddressProcessorHelper())->processAddress(
                $data['opg.poas.lpastore']['certificateProvider']['address'],
                'lpaStoreAddressType'
            );

            return [
                'first_name' => $data['opg.poas.lpastore']['certificateProvider']['firstNames'],
                'last_name' => $data['opg.poas.lpastore']['certificateProvider']['lastName'],
                'dob' => '1000-01-01', //temp setting should be null in prod
                'address' => $address,
            ];
        }

        throw new HttpException(400, 'Person type "' . $type . '" is not valid');
    }
}

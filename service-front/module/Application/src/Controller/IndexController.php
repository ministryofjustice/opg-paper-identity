<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\HttpException;
use Application\Services\SiriusApiService;
use DateTime;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * @psalm-import-type Lpa from SiriusApiService
 * @psalm-import-type Address from SiriusApiService
 *
 * @psalm-type Identity array{first_name: string, last_name: string, dob: string, address: string[]}
 */

class IndexController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
    ) {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function startAction(): Response
    {
        /** @var string[] $lpasQuery */
        $lpasQuery = $this->params()->fromQuery("lpas");
        $lpas = [];
        foreach ($lpasQuery as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data;
        }

//        die(json_encode($data));

//        if (! $this->checkLpaDonorDetails($lpas)) {
//            throw new HttpException(
//                400,
//                'These LPAs appear to relate to different donors.',
//            );
//        }

        /** @var string $type */
        $type = $this->params()->fromQuery("personType");
        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        $detailsData = $this->processLpaResponse($type, $lpas[0]);

        $case = $this->opgApiService->createCase(
            $detailsData['first_name'],
            $detailsData['last_name'],
            $detailsData['dob'],
            $type,
            $lpasQuery,
            $detailsData['address']
        );

        return $type === 'donor' ?
            $this->redirect()->toRoute('root/how_donor_confirms', ['uuid' => $case['uuid']]) :
            $this->redirect()->toRoute('root/cp_how_cp_confirms', ['uuid' => $case['uuid']]);
    }

    /**
     * @param Lpa $data
     * @return Identity
     */
    private function processLpaResponse(string $type, array $data): array
    {
        $lpaStoreAddressType = [
            'line1' => 'line1',
            'line2' => 'line2',
            'line3' => 'line3',
            'town' => 'town',
            'postcode' => 'postcode',
            'country' => 'country'
        ];

        $siriusAddressType = [
            'line1' => 'addressLine1',
            'line2' => 'addressLine2',
            'line3' => 'addressLine3',
            'town' => 'town',
            'postcode' => 'postcode',
            'country' => 'country'
        ];

        if ($type === 'donor') {
            if (! empty($data['opg.poas.lpastore'])) {
                $address = $this->processAddress($data['opg.poas.lpastore']['donor']['address'], $lpaStoreAddressType);

                return [
                    'first_name' => $data['opg.poas.lpastore']['donor']['firstNames'],
                    'last_name' => $data['opg.poas.lpastore']['donor']['lastName'],
                    'dob' => (new DateTime($data['opg.poas.lpastore']['donor']['dateOfBirth']))->format("Y-m-d"),
                    'address' => $address,
                ];
            }

            $address = $this->processAddress(
                $data['opg.poas.sirius']['donor'],
                $siriusAddressType
            );

            return [
                'first_name' => $data['opg.poas.sirius']['donor']['firstname'],
                'last_name' => $data['opg.poas.sirius']['donor']['surname'],
                'dob' => (new DateTime($data['opg.poas.sirius']['donor']['dob']))->format("Y-m-d"),
                'address' => $address,
            ];
        } elseif ($type === 'certificateProvider') {
            if ($data['opg.poas.lpastore'] === null) {
                throw new HttpException(
                    400,
                    'Cannot ID check this certificate provider as the LPA has not yet been submitted',
                );
            }

            $address = $this->processAddress(
                $data['opg.poas.lpastore']['certificateProvider']['address'],
                $lpaStoreAddressType
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

    /**
     * @param Address $address
     * @return string[]
     */
    public function processAddress(array $address, $addressType): array
    {
        $processedAddress = [];

        foreach ($addressType as $key => $value) {
            $processedAddress[$key] = array_key_exists($value, $address) ? $address[$value]: '';
        }

        return $processedAddress;
    }

    private function checkLpaDonorDetails(array $lpas): bool
    {
        foreach ($lpas as $key => $lpaRecord) {
            if ($key == 0) {
                $name = $lpaRecord['opg.poas.lpastore']['donor']['firstNames'] .
                    $lpaRecord['opg.poas.lpastore']['donor']['lastName'];
            } else {
                $nextName = $lpaRecord['opg.poas.lpastore']['donor']['firstNames'] .
                    $lpaRecord['opg.poas.lpastore']['donor']['lastName'];
                if ($name !== $nextName) {
                    return false;
                }
            }
        }
        return true;
    }
}

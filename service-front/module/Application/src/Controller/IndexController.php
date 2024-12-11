<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Exceptions\HttpException;
use Application\Forms\AbandonFlow;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\SiriusDataProcessorHelper;
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
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly LpaFormHelper $lpaFormHelper,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
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

        /** @var string $type */
        $type = $this->params()->fromQuery("personType");

        if (! $this->lpaFormHelper->lpaIdentitiesMatch($lpas, $type)) {
            $personTypeDescription = [
                'donor' => "Donors",
                'certificateProvider' => "Certificate Providers",
                'voucher' => "Vouchers"
            ];
            throw new HttpException(400, "These LPAs are for different {$personTypeDescription[$type]}");
        }

        $case = $this->siriusDataProcessorHelper->createPaperIdCase($type, $lpasQuery, $lpas[0]);

        $route = [
            'donor' => 'root/how_donor_confirms',
            'certificateProvider' => 'root/cp_how_cp_confirms',
            'voucher' => 'root/confirm_vouching'
        ];

        return $this->redirect()->toRoute($route[$type], ['uuid' => $case['uuid']]);
    }

    /**
     * @param Lpa $data
     * @return Identity
     */
    private function processLpaResponse(string $type, array $data): array
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

    public function abandonFlowAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $saveProgressData = [];
        $saveProgressData['last_page'] = $this->getRequest()->getQuery('last_page');
        $saveProgressData['timestamp'] = date("Y-m-d\TH:i:s\Z", time());
        $this->opgApiService->updateCaseProgress($uuid, $saveProgressData);

        $form = $this->createForm(AbandonFlow::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $siriusData = [
                "reference" => $uuid,
                "actorType" => $detailsData['personType'],
                "lpaIds" => $detailsData['lpas'],
                "time" => (new \DateTime('NOW'))->format('c'),
                "outcome" => "exit"
            ];

            $this->siriusApiService->abandonCase($siriusData, $this->getRequest());
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('last_page', $saveProgressData['last_page']);
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/abandoned_flow');
    }

    public function healthCheckAction(): ViewModel
    {
        $view = new ViewModel();

        $view->setVariable('status', json_encode([
            'OK' => true
        ]));
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        return $view->setTemplate('application/pages/healthcheck/healthcheck');
    }

    public function healthCheckServiceAction(): ViewModel
    {
        $view = new ViewModel();
        $ok = true;

        $siriusResponse = $this->siriusApiService->checkAuth($this->getRequest());
        if ($siriusResponse !== true) {
            $ok = false;
        }

        $apiResponse = $this->opgApiService->healthCheck();
        if ($apiResponse !== true) {
            $ok = false;
        }

        $response = [
            'OK' => $ok,
            'dependencies' => [
                'sirius' => [
                    'ok' => $siriusResponse
                ],
                'api' => [
                    'ok' => $apiResponse
                ]
            ]
        ];
        $view->setVariable('status', json_encode($response));
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        return $view->setTemplate('application/pages/healthcheck/healthcheck');
    }
}

<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Exceptions\HttpException;
use Application\Forms\AbandonFlow;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\LpaStatusTypeHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
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
        private readonly string $siriusPublicUrl,
    ) {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function startAction(): Response|ViewModel
    {
        $view = new ViewModel();
        /** @var string[] $lpasQuery */
        $lpasQuery = $this->params()->fromQuery("lpas");
        /** @var string $type */
        $type = $this->params()->fromQuery("personType");

        $lpas = [];
        $unfoundLpas = [];
        foreach ($lpasQuery as $key => $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());

            if (array_key_exists('status', $data) && $data['status'] == '404') {
                $unfoundLpas[] = $lpaUid;
                $view->setVariables([
                    'sirius_url' => $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $lpasQuery[0],
                    'details_data' => [
                        'personType' => $type,
                        'firstName' => '',
                        'lastName' => '',
                    ]
                ]);
                unset($lpasQuery[$key]);
            } else {
                $lpas[] = $data;
            }
        }

        $lpasQuery = array_values($lpasQuery);

        if (empty($lpas)) {
            $lpsString = implode(", ", $unfoundLpas);
            $view->setVariable('message', 'LPA not found for ' . $lpsString);
            return $view->setTemplate('application/pages/cannot_start');
        }

        if (! $this->lpaFormHelper->lpaIdentitiesMatch($lpas, $type)) {
            $personTypeDescription = [
                'donor' => "Donors",
                'certificateProvider' => "Certificate Providers",
                'voucher' => "Vouchers"
            ];
            throw new HttpException(
                400,
                "These LPAs are for different {$personTypeDescription[$type]}"
            );
        }

        try {
            $lpaStatusCheck = new LpaStatusTypeHelper($lpas[0], $type);

            if (! $lpaStatusCheck->isStartable()) {
                $lpaStatusTypeCheck = $lpaStatusCheck->getStatus() === 'registered' ?
                    "The identity check has already been completed" :
                    "ID check has status: " . $lpaStatusCheck->getStatus() . " and cannot be started";

                $view = new ViewModel();
                $view->setVariables([
                    'message' => $lpaStatusTypeCheck,
                    'sirius_url' => $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $lpasQuery[0],
                    'details_data' => $this->constructDetailsDataBeforeCreatedCase($lpas[0], $type)
                ]);
                return $view->setTemplate('application/pages/cannot_start');
            }
        } catch (\Exception $exception) {
            throw new HttpException(400, $exception->getMessage());
        }

        $case = $this->siriusDataProcessorHelper->createPaperIdCase($type, $lpasQuery, $lpas[0]);

        if ($type === 'voucher') {
            $redirect = 'root/confirm_vouching';
        } else {
            $redirect = 'root/how_will_you_confirm';
        }
        return $this->redirect()->toRoute($redirect, ['uuid' => $case['uuid']]);
    }

    private function constructDetailsDataBeforeCreatedCase(array $lpa, string $personType): array
    {
        $processed = $this->siriusDataProcessorHelper->processLpaResponse(
            $personType,
            $lpa
        );

        return [
            'personType' => $personType,
            'firstName' => $processed['first_name'],
            'lastName' => $processed['last_name'],
        ];
    }

    public function abandonFlowAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $request = $this->getRequest();

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lastPage = $request->getQuery('last_page');

        $form = $this->createForm(AbandonFlow::class);

        if ($request->isPost() && $form->isValid()) {
            $caseProgressData = $detailsData['caseProgress'] ?? [];

            $caseProgressData['abandonedFlow'] = [
                'last_page' => $lastPage,
                'timestamp' => date("Y-m-d\TH:i:s\Z", time()),
            ];

            $this->opgApiService->updateCaseProgress($uuid, $caseProgressData);
            $this->opgApiService->sendIdentityCheck($uuid);

            $postData = $request->getPost()->toArray();
            $noteDescription = "Reason: " . $this->mapReason($postData['reason']);
            $noteDescription .= "\n\n" . $postData['notes'];

            $lpas = $detailsData["lpas"];
            foreach ($lpas as $lpaUid) {
                $this->siriusApiService->addNote(
                    $request,
                    $lpaUid,
                    "ID Check Abandoned",
                    "ID Check Incomplete",
                    $noteDescription
                );
            }

            $siriusUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];
            return $this->redirect()->toUrl($siriusUrl);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('last_page', $lastPage);
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/abandoned_flow');
    }

    private function mapReason(string $reason): string
    {
        $reasons = [
            'cd' => 'Call dropped',
            'nc' => 'Caller not able to complete at this time',
            'ot' => 'Other'
        ];

        return $reasons[$reason] ?? 'Unknown';
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

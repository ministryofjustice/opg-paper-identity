<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\PersonType;
use Application\Exceptions\HttpException;
use Application\Forms\AbandonFlow;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\LpaStatusTypeHelper;
use Application\Helpers\SendSiriusNoteHelper;
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
        private readonly SendSiriusNoteHelper $sendNoteHelper,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly string $siriusPublicUrl,
    ) {
    }

    public function startAction(): Response|ViewModel
    {
        $view = new ViewModel();
        /** @var string[] $lpasQuery */
        $lpasQuery = $this->params()->fromQuery("lpas");
        $type = $this->params()->fromQuery("personType");
        try {
            /** @var PersonType $personType */
            $personType = PersonType::from($type);
        } catch (\ValueError) {
            throw new HttpException(
                400,
                "Person type '$type' is not valid"
            );
        }

        $lpas = [];
        $unfoundLpas = [];
        foreach ($lpasQuery as $key => $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());

            if (empty($data)) {
                $unfoundLpas[] = $lpaUid;
                $view->setVariables([
                    'sirius_url' => $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $lpasQuery[0],
                    'details_data' => [
                        'personType' => $personType,
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

        if (! $this->lpaFormHelper->lpaIdentitiesMatch($lpas, $personType)) {
            $personTypeDescription = [
                'donor' => "Donors",
                'voucher' => "Donors",
                'certificateProvider' => "Certificate Providers",
            ];
            throw new HttpException(
                400,
                "These LPAs are for different {$personTypeDescription[$personType->value]}"
            );
        }

        try {
            $lpaStatusCheck = new LpaStatusTypeHelper($lpas[0], $personType);

            if (! $lpaStatusCheck->isStartable()) {
                $lpaStatusTypeCheck = $lpaStatusCheck->getStatus() === 'registered' ?
                    "The identity check has already been completed" :
                    "ID check has status: " . $lpaStatusCheck->getStatus() . " and cannot be started";

                $view = new ViewModel();
                $view->setVariables([
                    'message' => $lpaStatusTypeCheck,
                    'sirius_url' => $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $lpasQuery[0],
                    'details_data' => $this->constructDetailsDataBeforeCreatedCase($lpas[0], $personType)
                ]);
                return $view->setTemplate('application/pages/cannot_start');
            }
        } catch (\Exception $exception) {
            throw new HttpException(400, $exception->getMessage());
        }

        $case = $this->siriusDataProcessorHelper->createPaperIdCase($personType, $lpasQuery, $lpas[0]);

        if ($personType === PersonType::Voucher) {
            $redirect = 'root/confirm_vouching';
        } else {
            $redirect = 'root/how_will_you_confirm';
        }
        return $this->redirect()->toRoute($redirect, ['uuid' => $case['uuid']]);
    }

    private function constructDetailsDataBeforeCreatedCase(array $lpa, PersonType $personType): array
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

            $this->sendNoteHelper->sendAbandonFlowNote(
                $postData['reason'],
                $postData['notes'],
                $detailsData['lpas'],
                $this->getRequest()
            );
            $this->sendNoteHelper->sendBlockedRoutesNote($detailsData, $this->getRequest());
            $siriusUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];
            return $this->redirect()->toUrl($siriusUrl);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('last_page', $lastPage);
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

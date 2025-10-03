<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\LpaStatusType;
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

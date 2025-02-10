<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Exceptions\HttpException;
use Application\Forms\AbandonFlow;
use Application\Helpers\LpaFormHelper;
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

    public function startAction(): Response
    {
        /** @var string[] $lpasQuery */
        $lpasQuery = $this->params()->fromQuery("lpas");

        $lpas = [];
        foreach ($lpasQuery as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data;
        }

        if (empty($lpas)) {
            $lpsString = implode(", ", $lpasQuery);
            throw new HttpException(404, "LPAs not found for {$lpsString}");
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

        $this->ensureIdentityCheckHasNotAlreadyBeenPerformed($type, $lpas[0]);

        $case = $this->siriusDataProcessorHelper->createPaperIdCase($type, $lpasQuery, $lpas[0]);

        if ($type === 'voucher') {
            $redirect = 'root/confirm_vouching';
        } else {
            $redirect = 'root/how_will_you_confirm';
        }
        return $this->redirect()->toRoute($redirect, ['uuid' => $case['uuid']]);
    }

    /**
     * @param string $type
     * @psalm-param Lpa $lpaData
     * @return void
     * @throws HttpException
     */
    private function ensureIdentityCheckHasNotAlreadyBeenPerformed(string $type, array $lpaData): void
    {
        if ($type === 'donor' && isset($lpaData['opg.poas.lpastore']['donor']['identityCheck'])) {
            throw new HttpException(400, "ID check has already been completed");
        }

        if (
            $type === 'certificateProvider'
            && isset($lpaData['opg.poas.lpastore']['certificateProvider']['identityCheck'])
        ) {
            throw new HttpException(400, "ID check has already been completed");
        }
    }

    public function abandonFlowAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $caseProgressData = $detailsData['caseProgress'] ?? [];

        $caseProgressData['abandonedFlow'] = [
            'last_page' => $this->getRequest()->getQuery('last_page'),
            'timestamp' => date("Y-m-d\TH:i:s\Z", time()),
        ];

        $this->opgApiService->updateCaseProgress($uuid, $caseProgressData);

        $form = $this->createForm(AbandonFlow::class);

        $request = $this->getRequest();

        if ($request->isPost() && $form->isValid()) {
            $this->opgApiService->abandonFlow($uuid);

            $reason = $request->getPost("reason");
            $noteDescription = "Reason: " . $this->mapReason($reason->toString());
            $noteDescription .= "\n\n" . $request->getPost("notes");

            $this->siriusApiService->addNote(
                $request,
                $detailsData["lpas"][0],
                "ID Check Abandoned",
                "ID Check Incomplete",
                $noteDescription
            );

            $siriusUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];
            return $this->redirect()->toUrl($siriusUrl);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('last_page', $caseProgressData['abandonedFlow']['last_page']);
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

<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\LpaTypes;
use Application\Forms\ConfirmCourtOfProtection;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CourtOfProtectionFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly string $siriusPublicUrl,
    ) {
    }

    public function registerAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $form = $this->createForm(ConfirmCourtOfProtection::class);
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        $hasFraudMarker = isset($detailsData["caseProgress"]["fraudScore"]["decision"])
            && $detailsData["caseProgress"]["fraudScore"]["decision"] === "STOP";

        $view->setVariable('has_fraud_marker', $hasFraudMarker);

        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);

            if (isset($lpasData['opg.poas.lpastore'])) {
                $name = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                    $lpasData['opg.poas.lpastore']['donor']['lastName'];

                $type = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);
            } else {
                $name = $lpasData['opg.poas.sirius']['donor']['firstname'] . " " .
                    $lpasData['opg.poas.sirius']['donor']['surname'];

                $type = LpaTypes::fromName($lpasData['opg.poas.sirius']['caseSubtype']);
            }

            $lpaDetails[$lpa] = [
                'name' => $name,
                'type' => $type
            ];
        }

        $view->setVariable('lpa_details', $lpaDetails);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $this->opgApiService->sendSiriusEvent($uuid, 'cop-started');
            return $this->redirect()->toRoute("root/court_of_protection_what_next", ['uuid' => $uuid]);
        }

        return $view->setTemplate('application/pages/court_of_protection');
    }

    public function whatNextAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        $siriusUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];
        $view->setVariable('sirius_url', $siriusUrl);

        return $view->setTemplate('application/pages/court_of_protection_what_next');
    }
}

<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\IdMethod;
use Application\Forms\ChooseVouching;
use Application\Forms\FinishIDCheck;
use Application\Helpers\DateProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\LpaTypes;
use Application\Enums\SiriusDocument;
use Psr\Log\LoggerInterface;

class DonorFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly string $siriusPublicUrl,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function whatIsVouchingAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(ChooseVouching::class);
        $view->setVariable('details_data', $detailsData);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->getRequest()->getPost()->toArray();
            if ($formData['chooseVouching'] == 'yes') {
                $this->opgApiService->sendSiriusEvent($uuid, 'vouch-started');
                return $this->redirect()->toRoute("root/vouching_what_happens_next", ['uuid' => $uuid]);
            } else {
                return $this->redirect()->toRoute("root/how_will_you_confirm", ['uuid' => $uuid]);
            }
        }

        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/what_is_vouching');
    }

    public function vouchingWhatHappensNextAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $siriusEditUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('sirius_edit_url', $siriusEditUrl);

        return $view->setTemplate('application/pages/vouching_what_happens_next');
    }

    public function donorDetailsMatchCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        try {
            $this->siriusDataProcessorHelper->updatePaperIdCaseFromSirius($uuid, $this->getRequest());
        } catch (\Exception $e) {
            $this->logger->error('Unable to update paper id case from Sirius', ['exception' => $e]);
        }

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $siriusEditUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        $view->setVariables([
            'details_data' => $detailsData,
            'formattedDob' => DateProcessorHelper::formatDate($detailsData['dob']),
            'uuid' => $uuid,
            'next_page' => './donor-lpa-check',
            'sirius_edit_url' => $siriusEditUrl
        ]);

        return $view->setTemplate('application/pages/donor_details_match_check');
    }

    public function donorLpaCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('lpas', $detailsData['lpas']);
        $view->setVariable('lpa_count', count($detailsData['lpas']));

        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);

            if (! empty($lpasData['opg.poas.lpastore'])) {
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

        if (count($this->getRequest()->getPost())) {
//            $data = $this->getRequest()->getPost();
            // not yet implemented
//          $response =  $this->opgApiService->saveLpaRefsToIdCheck();

            /**
             * @psalm-suppress PossiblyUndefinedArrayOffset
             */
            if ($detailsData['idMethodIncludingNation']['id_route'] == 'POST_OFFICE') {
                $this->redirect()
                    ->toRoute("root/find_post_office_branch", ['uuid' => $uuid]);
            } else {
                switch ($detailsData['idMethodIncludingNation']['id_method']) {
                    case IdMethod::PassportNumber->value:
                        $this->redirect()
                            ->toRoute("root/passport_number", ['uuid' => $uuid]);
                        break;

                    case IdMethod::DrivingLicenseNumber->value:
                        $this->redirect()
                            ->toRoute("root/driving_licence_number", ['uuid' => $uuid]);
                        break;

                    case IdMethod::NationalInsuranceNumber->value:
                        $this->redirect()
                            ->toRoute("root/national_insurance_number", ['uuid' => $uuid]);
                        break;

                    default:
                        break;
                }
            }
        }

        return $view->setTemplate('application/pages/donor_lpa_check');
    }

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $form = $this->createForm(FinishIDCheck::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->getRequest()->getPost()->toArray();

            $this->opgApiService->updateCaseAssistance($uuid, $formData['assistance'], $formData['details']);
            $this->redirect()->toUrl($this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0]);
        }

        $view = new ViewModel();
        $view->setVariable('form', $form);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/identity_check_passed');
    }

    public function identityCheckFailedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $lpaDetails[$lpa] = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];
        }

        $view = new ViewModel();

        $view->setVariable('lpas_data', $lpaDetails);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/identity_check_failed');
    }

    public function thinFileFailureAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/thin_file_failure');
    }

    public function provingIdentityAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/proving_identity');
    }

    public function removeLpaAction(): Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpa = $this->params()->fromRoute("lpa");

        $this->opgApiService->updateCaseWithLpa($uuid, $lpa, true);

        return $this->redirect()->toRoute("root/donor_lpa_check", ['uuid' => $uuid]);
    }
}

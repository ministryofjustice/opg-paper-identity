<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Forms\ChooseVouching;
use Application\Forms\FinishIDCheck;
use Application\Helpers\DateProcessorHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class DonorFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly string $siriusPublicUrl,
        private readonly SendSiriusNoteHelper $sendNoteHelper,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
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
                $this->opgApiService->sendIdentityCheck($uuid);
                $this->sendNoteHelper->sendBlockedRoutesNote($detailsData, $this->getRequest());
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

        $this->siriusDataProcessorHelper->updatePaperIdCaseFromSirius($uuid, $this->getRequest());

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

    public function donorLpaCheckAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('lpas', $detailsData['lpas']);
        $view->setVariable('lpa_count', count($detailsData['lpas']));

        $view->setVariable(
            'lpa_details',
            $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $this->request)
        );

        if ($this->getRequest()->isPost()) {
            $redirect = null;

            /**
             * @psalm-suppress PossiblyUndefinedArrayOffset
             */
            if ($detailsData['idMethod']['idRoute'] == IdRoute::POST_OFFICE->value) {
                $redirect = $this->redirect()
                    ->toRoute("root/find_post_office_branch", ['uuid' => $uuid]);
            } else {
                switch ($detailsData['idMethod']['docType']) {
                    case DocumentType::Passport->value:
                        $redirect = $this->redirect()
                            ->toRoute("root/passport_number", ['uuid' => $uuid]);
                        break;

                    case DocumentType::DrivingLicence->value:
                        $redirect = $this->redirect()
                            ->toRoute("root/driving_licence_number", ['uuid' => $uuid]);
                        break;

                    case DocumentType::NationalInsuranceNumber->value:
                        $redirect = $this->redirect()
                            ->toRoute("root/national_insurance_number", ['uuid' => $uuid]);
                        break;

                    default:
                        break;
                }
            }

            if (! is_null($redirect)) {
                return $redirect;
            }
        }

        return $view->setTemplate('application/pages/donor_lpa_check');
    }

    public function identityCheckPassedAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $form = $this->createForm(FinishIDCheck::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->getRequest()->getPost()->toArray();

            $this->opgApiService->updateCaseAssistance($uuid, $formData['assistance'], $formData['details']);
            return $this->redirect()->toUrl($this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0]);
        }

        $view = new ViewModel();
        $view->setVariable('form', $form);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/identity_check_passed');
    }

    public function thinFileFailureAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/thin_file_failure');
    }

    public function removeLpaAction(): Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpa = $this->params()->fromRoute("lpa");

        $this->opgApiService->updateCaseWithLpa($uuid, $lpa, true);

        return $this->redirect()->toRoute("root/donor_lpa_check", ['uuid' => $uuid]);
    }
}

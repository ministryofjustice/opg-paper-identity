<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\IdMethod;
use Application\Forms\IdMethod as IdMethodForm;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportDate;
use Application\Forms\PassportNumber;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\Country;
use Application\Services\SiriusApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\LpaTypes;
use Application\Enums\SiriusDocument;

class DonorFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly array $config,
        private readonly string $siriusPublicUrl,
    ) {
    }

    public function howWillDonorConfirmAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/how_will_the_donor_confirm'];
        $uuid = $this->params()->fromRoute("uuid");
        $view = new ViewModel();
        $dateSubForm = $this->createForm(PassportDate::class);
        $form = $this->createForm(IdMethodForm::class);

        $serviceAvailability = $this->opgApiService->getServiceAvailability();

        $identityDocs = [];
        foreach ($this->config['opg_settings']['identity_documents'] as $key => $value) {
            if ($serviceAvailability->getProcessedStatus()[$key] === true) {
                $identityDocs[$key] = $value;
            }
        }

        $optionsData = $identityDocs;

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('form', $form);
        $view->setVariable('options_data', $optionsData);
        $view->setVariable('service_availability', $serviceAvailability->toArray());
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost()->toArray();
            if (array_key_exists('check_button', $formData)) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
                $view->setVariables($formProcessorResponseDto->getVariables());
            } else {
                if ($form->isValid()) {
                    if ($formData['id_method'] == IdMethod::PostOffice->value) {
                        $data = [
                            'id_route' => 'POST_OFFICE',
                        ];
                        $this->opgApiService->updateIdMethodWithCountry(
                            $uuid,
                            $data
                        );
                        $returnRoute = "root/post_office_documents";
                    } elseif ($formData['id_method'] == IdMethod::OnBehalf->value) {
                        $returnRoute = "root/what_is_vouching";
                    } else {
                        $data = [
                            'id_route' => 'TELEPHONE',
                            'id_country' => Country::GBR->value,
                            'id_method' => $formData['id_method']
                        ];
                        $this->opgApiService->updateIdMethodWithCountry(
                            $uuid,
                            $data
                        );
                        $returnRoute = "root/donor_details_match_check";
                    }
                    return $this->redirect()->toRoute($returnRoute, ['uuid' => $uuid]);
                }
            }
        }

        return $view->setTemplate($templates['default']);
    }

    public function whatIsVouchingAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost()->toArray();
            if ($formData['confirm_vouching'] == 'yes') {
                $pdf = $this->siriusApiService->sendDocument(
                    $detailsData,
                    SiriusDocument::VouchInvitation,
                    $this->getRequest()
                );
                // if any other status then error will be raised by framework and error page displayed
                if ($pdf['status'] === 201) {
                    return $this->redirect()->toRoute("root/vouching_what_happens_next", ['uuid' => $uuid]);
                }
            } else {
                return $this->redirect()->toRoute("root/how_donor_confirms", ['uuid' => $uuid]);
            }
        }

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
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        if ($detailsData['idMethodIncludingNation']['id_route'] != 'TELEPHONE') {
            $nextPage = './post-office-donor-lpa-check';
        } else {
            $nextPage = './donor-lpa-check';
        }

        $detailsData['formatted_dob'] = (new \DateTime($detailsData['dob']))->format("d F Y");

        $view = new ViewModel();

        $siriusEditUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        $view->setVariables([
            'details_data' => $detailsData,
            'uuid' => $uuid,
            'next_page' => $nextPage,
            'sirius_edit_url' => $siriusEditUrl
        ]);

        return $view->setTemplate('application/pages/donor_details_match_check');
    }

    public function donorIdCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $optionsdata = $this->config['opg_settings']['identity_labels'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/donor_id_check');
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
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
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
                    ->toRoute("root/post_office_documents", ['uuid' => $uuid]);
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

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $serviceAvailability = $this->opgApiService->getServiceAvailability();

        $templates = $this->config['opg_settings']['template_options']['NATIONAL_INSURANCE_NUMBER'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);
        $view->setVariable('service_availability', $serviceAvailability->toArray());

        $form = $this->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $form,
                $templates
            );
            $view->setVariables($formProcessorResponseDto->getVariables());

            $template = $formProcessorResponseDto->getTemplate();
            if ($template == 'application/pages/fraud_failure') {
                $view->setVariable(
                    'fraud_message',
                    'Recommend the Post Office route. Alternatively, consider the Court of Protection.'
                );
            }
            return $view->setTemplate($template);
        }
        return $view->setTemplate($templates['default']);
    }

    public function drivingLicenceNumberAction(): ViewModel
    {
        $serviceAvailability = $this->opgApiService->getServiceAvailability();

        $templates = $this->config['opg_settings']['template_options']['DRIVING_LICENCE'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);
        $view->setVariable('service_availability', $serviceAvailability->toArray());

        $form = $this->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formProcessorResponseDto = $this->formProcessorHelper->processDrivingLicenceForm(
                $uuid,
                $form,
                $templates
            );

            $view->setVariables($formProcessorResponseDto->getVariables());
            $template = $formProcessorResponseDto->getTemplate();
            if ($template == 'application/pages/fraud_failure') {
                $view->setVariable(
                    'fraud_message',
                    'Recommend the Post Office route. Alternatively, consider the Court of Protection.'
                );
            }
            return $view->setTemplate($template);
        }
        return $view->setTemplate($templates['default']);
    }

    public function passportNumberAction(): ViewModel
    {
        $serviceAvailability = $this->opgApiService->getServiceAvailability();

        $templates = $this->config['opg_settings']['template_options']['PASSPORT'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);
        $view->setVariable('service_availability', $serviceAvailability->toArray());

        $form = $this->createForm(PassportNumber::class);
        $dateSubForm = $this->createForm(PassportDate::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('dob_full', date_format(date_create($detailsData['dob']), "d F Y"));
        $view->setVariable('form', $form);
        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('details_open', false);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $data = $formData->toArray();
            $view->setVariable('passport', $data['passport']);

            if (array_key_exists('check_button', $data)) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $formData,
                    $dateSubForm,
                    $templates
                );
            } else {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportForm(
                    $uuid,
                    $form,
                    $templates
                );
                $view->setVariable(
                    'passport_indate',
                    array_key_exists('inDate', $data) ?
                        ucwords($data['inDate']) :
                        'no'
                );
            }
            $view->setVariables($formProcessorResponseDto->getVariables());
            $template = $formProcessorResponseDto->getTemplate();
            if ($template == 'application/pages/fraud_failure') {
                $view->setVariable(
                    'fraud_message',
                    'Recommend the Post Office route. Alternatively, consider the Court of Protection.'
                );
            }
            return $view->setTemplate($template);
        }
        return $view->setTemplate($templates['default']);
    }

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
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

        return $view->setTemplate('application/pages/identity_check_passed');
    }

    public function identityCheckFailedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
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

    public function fraudCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);


    }
}

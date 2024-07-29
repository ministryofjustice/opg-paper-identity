<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\IdMethod;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportDate;
use Application\Forms\PassportNumber;
use Application\Helpers\FormProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\LpaTypes;

class DonorFlowController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly array $config,
    ) {
    }

    private function getRoute(): string
    {
        $route = $this->getEvent()->getRouteMatch();
        return is_null($route) ? "" : $route->getMatchedRouteName();
    }

    public function howWillDonorConfirmAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/how_will_the_donor_confirm'];
        $uuid = $this->params()->fromRoute("uuid");
        $view = new ViewModel();
        $dateSubForm = (new AttributeBuilder())->createForm(PassportDate::class);

        $optionsdata = $this->config['opg_settings']['identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);
        $view->setVariable('route', $this->getRoute());

        if (count($this->getRequest()->getPost())) {
            if (count($this->getRequest()->getPost())) {
                $formData = $this->getRequest()->getPost()->toArray();
                if (array_key_exists('check_button', $formData)) {
                    $dateSubForm->setData($this->getRequest()->getPost());
                    $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                        $uuid,
                        $this->getRequest()->getPost(),
                        $dateSubForm,
                        $templates
                    );
                    $view->setVariables($formProcessorResponseDto->getVariables());
                } else {
                    $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);
                    if ($formData['id_method'] == IdMethod::PostOffice->value) {
                        return $this->redirect()->toRoute("root/post_office_documents", ['uuid' => $uuid]);
                    } else {
                        return $this->redirect()->toRoute("root/donor_details_match_check", ['uuid' => $uuid]);
                    }
                }
            }
        }

        return $view->setTemplate($templates['default']);
    }

    public function donorDetailsMatchCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $detailsData['formatted_dob'] = (new \DateTime($detailsData['dob']))->format("d F Y");

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/donor_details_match_check');
    }

    public function donorIdCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $optionsdata = $this->config['opg_settings']['identity_methods'];
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
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $name = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];

            /**
             * @psalm-suppress PossiblyNullArrayAccess
             * @psalm-suppress InvalidArrayOffset
             * @psalm-suppress PossiblyNullArgument
             */
            $type = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);

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

            switch ($detailsData['idMethod']) {
                case 'pn':
                    $this->redirect()
                        ->toRoute("root/passport_number", ['uuid' => $uuid]);
                    break;

                case 'dln':
                    $this->redirect()
                        ->toRoute("root/driving_licence_number", ['uuid' => $uuid]);
                    break;

                case 'nin':
                    $this->redirect()
                        ->toRoute("root/national_insurance_number", ['uuid' => $uuid]);
                    break;

                case 'po':
                    $this->redirect()
                        ->toRoute("root/post_office_documents", ['uuid' => $uuid]);
                    break;

                default:
                    break;
            }
        }

        return $view->setTemplate('application/pages/donor_lpa_check');
    }

    public function addressVerificationAction(): ViewModel
    {
        $view = new ViewModel();

        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);
        $data = $this->opgApiService->getAddressVerificationData();

        $view->setVariable('options_data', $data);

        return $view->setTemplate('application/pages/address_verification');
    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/national_insurance_number',
            'success' => 'application/pages/national_insurance_number_success',
            'fail' => 'application/pages/national_insurance_number_fail'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = (new AttributeBuilder())->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $this->getRequest()->getPost(),
                $form,
                $templates
            );
            $view->setVariables($formProcessorResponseDto->getVariables());

            return $view->setTemplate($formProcessorResponseDto->getTemplate());
        }
        return $view->setTemplate($templates['default']);
    }

    public function drivingLicenceNumberAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/driving_licence_number',
            'success' => 'application/pages/driving_licence_number_success',
            'fail' => 'application/pages/driving_licence_number_fail'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = (new AttributeBuilder())->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formProcessorResponseDto = $this->formProcessorHelper->processDrivingLicenceForm(
                $uuid,
                $this->getRequest()->getPost(),
                $form,
                $templates
            );

            $view->setVariables($formProcessorResponseDto->getVariables());

            return $view->setTemplate($formProcessorResponseDto->getTemplate());
        }
        return $view->setTemplate($templates['default']);
    }

    public function passportNumberAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/passport_number',
            'success' => 'application/pages/passport_number_success',
            'fail' => 'application/pages/passport_number_fail'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = (new AttributeBuilder())->createForm(PassportNumber::class);
        $dateSubForm = (new AttributeBuilder())->createForm(PassportDate::class);
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

            if (array_key_exists('check_button', $formData->toArray())) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
            } else {
                $view->setVariable('passport_indate', ucwords($data['inDate']));
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportForm(
                    $uuid,
                    $this->getRequest()->getPost(),
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
            return $view->setTemplate($formProcessorResponseDto->getTemplate());
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
}

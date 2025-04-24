<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\DocumentType;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportDate;
use Application\Forms\PassportNumber;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\DateProcessorHelper;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class DocumentCheckController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    private const COMPLETE_MESSAGE = 'The identity check has already been completed';

    private const CANNOT_START = "application/pages/cannot_start";

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly array $config,
        private readonly string $siriusPublicUrl
    ) {
    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $routeAvailability = $this->opgApiService->getRouteAvailability($uuid);

        $templates = $this->config['opg_settings']['template_options'][DocumentType::NationalInsuranceNumber->value];
        $template = $templates['default'];
        $view = new ViewModel();
        $view->setVariable('uuid', $uuid);
        $view->setVariable('route_availability', $routeAvailability);

        $form = $this->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (isset($detailsData['identityCheckPassed'])) {
            $view->setVariable('message', self::COMPLETE_MESSAGE);
            $view->setVariable('sirius_url', $this->siriusPublicUrl);
            return $view->setTemplate(self::CANNOT_START);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $form,
                $templates
            );
            $gotVariables = $formProcessorResponseDto->getVariables();
            $view->setVariables($gotVariables);

            if ($gotVariables['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    DocumentType::NationalInsuranceNumber->value
                );
            } elseif ($gotVariables['validity'] === 'MULTIPLE_MATCH') {
                $template = $templates['amb_fail'];
            } else {
                $template = $templates['fail'];
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    DocumentType::NationalInsuranceNumber->value,
                    false
                );
            }
            return $view->setTemplate($template);
        }
        return $view->setTemplate($template);
    }

    public function drivingLicenceNumberAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $routeAvailability = $this->opgApiService->getRouteAvailability($uuid);

        $templates = $this->config['opg_settings']['template_options'][DocumentType::DrivingLicence->value];
        $template = $templates['default'];
        $view = new ViewModel();
        $view->setVariable('uuid', $uuid);
        $view->setVariable('route_availability', $routeAvailability);

        $form = $this->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (isset($detailsData['identityCheckPassed'])) {
            $view->setVariable('message', self::COMPLETE_MESSAGE);
            return $view->setTemplate(self::CANNOT_START);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));

        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processDrivingLicenceForm(
                $uuid,
                $form,
                $templates
            );
            $view->setVariables($formProcessorResponseDto->getVariables());

            if ($formProcessorResponseDto->getVariables()['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
                $this->opgApiService->updateCaseSetDocumentComplete($uuid, DocumentType::DrivingLicence->value);
            } else {
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    DocumentType::DrivingLicence->value,
                    false
                );
                $template = $templates['fail'];
            }
            return $view->setTemplate($template);
        }
        return $view->setTemplate($template);
    }

    public function passportNumberAction(): ViewModel
    {
        $templates = $this->config['opg_settings']['template_options'][DocumentType::Passport->value];
        $template = $templates['default'];
        $uuid = $this->params()->fromRoute("uuid");
        $routeAvailability = $this->opgApiService->getRouteAvailability($uuid);
        $view = new ViewModel();
        $view->setVariable('uuid', $uuid);
        $view->setVariable('route_availability', $routeAvailability);

        $form = $this->createForm(PassportNumber::class);
        $dateSubForm = $this->createForm(PassportDate::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (isset($detailsData['identityCheckPassed'])) {
            $view->setVariable('message', self::COMPLETE_MESSAGE);
            return $view->setTemplate(self::CANNOT_START);
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));
        $view->setVariable('form', $form);
        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('details_open', false);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            $data = $formData->toArray();

            if (array_key_exists('check_button', $data)) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $formData,
                    $dateSubForm,
                    $templates
                );
            } else {
                if ($form->isValid()) {
                    $view->setVariable('passport', $data['passport']);

                    $formProcessorResponseDto = $this->formProcessorHelper->processPassportForm(
                        $uuid,
                        $form,
                        $templates
                    );
                    $view->setVariable(
                        'passport_indate',
                        array_key_exists('inDate', $data) ? ucwords($data['inDate']) : 'no'
                    );

                    $view->setVariables($formProcessorResponseDto->getVariables());

                    if ($formProcessorResponseDto->getVariables()['validity'] === 'PASS') {
                        $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                        $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
                        $this->opgApiService->updateCaseSetDocumentComplete($uuid, DocumentType::Passport->value);
                    } else {
                        $this->opgApiService->updateCaseSetDocumentComplete(
                            $uuid,
                            DocumentType::Passport->value,
                            false
                        );
                        $template = $templates['fail'];
                    }
                    return $view->setTemplate($template);
                }
            }
            if (isset($formProcessorResponseDto)) {
                $view->setVariables($formProcessorResponseDto->getVariables());
            }
        }
        return $view->setTemplate($template);
    }
}

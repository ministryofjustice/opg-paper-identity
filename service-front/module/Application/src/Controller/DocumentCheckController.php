<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\IdMethod;
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

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly array $config,
    ) {
    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);

        $templates = $this->config['opg_settings']['template_options']['NATIONAL_INSURANCE_NUMBER'];
        $template = $templates['default'];
        $view = new ViewModel();
        $view->setVariable('uuid', $uuid);
        $view->setVariable('service_availability', $serviceAvailability);

        $form = $this->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $form,
                $templates
            );
            $view->setVariables($formProcessorResponseDto->getVariables());

            if ($formProcessorResponseDto->getVariables()['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
                $this->opgApiService->updateCaseSetDocumentComplete($uuid, IdMethod::NationalInsuranceNumber->value);
            } else {
                $template = $templates['fail'];
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    IdMethod::NationalInsuranceNumber->value,
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
        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);

        $templates = $this->config['opg_settings']['template_options']['DRIVING_LICENCE'];
        $template = $templates['default'];
        $view = new ViewModel();
        $view->setVariable('uuid', $uuid);
        $view->setVariable('service_availability', $serviceAvailability);

        $form = $this->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

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
                $this->opgApiService->updateCaseSetDocumentComplete($uuid, IdMethod::DrivingLicenseNumber->value);
            } else {
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    IdMethod::DrivingLicenseNumber->value,
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
        $templates = $this->config['opg_settings']['template_options']['PASSPORT'];
        $template = $templates['default'];
        $uuid = $this->params()->fromRoute("uuid");
        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);
        $view = new ViewModel();
        $view->setVariable('uuid', $uuid);
        $view->setVariable('service_availability', $serviceAvailability);

        $form = $this->createForm(PassportNumber::class);
        $dateSubForm = $this->createForm(PassportDate::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

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
                        $this->opgApiService->updateCaseSetDocumentComplete($uuid, IdMethod::PassportNumber->value);
                    } else {
                        $this->opgApiService->updateCaseSetDocumentComplete(
                            $uuid,
                            IdMethod::PassportNumber->value,
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

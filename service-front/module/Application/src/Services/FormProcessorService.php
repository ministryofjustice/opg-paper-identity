<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use Application\Services\DTO\FormProcessorResponseDto;
use Laminas\Stdlib\Parameters;
use Laminas\Form\FormInterface;
use Laminas\View\Model\ViewModel;

class FormProcessorService
{
    public function __construct(private OpgApiServiceInterface $opgApiService)
    {
    }

    public function processDrivingLicenceForm(
        Parameters $formData,
        FormInterface $form,
        ViewModel $view,
        array $templates = []
    ): ViewModel {
        $form->setData($formData);
        $validFormat = $form->isValid();

        if ($validFormat) {
            $view->setVariable('dln_data', $formData);
            $validDln = $this->opgApiService->checkDlnValidity($formData['dln']);

            if ($validDln === 'PASS') {
                return $view->setTemplate($templates['success']);
            }
            return $view->setTemplate($templates['fail']);
        }
        return $view->setTemplate($templates['default']);
    }

    public function processNationalInsuranceNumberForm(
        Parameters $formData,
        FormInterface $form,
        ViewModel $view,
        array $templates = []
    ): ViewModel {
        $form->setData($formData);
        $validFormat = $form->isValid();

        if ($validFormat) {
            $view->setVariable('nino_data', $formData);
            $validNino = $this->opgApiService->checkNinoValidity($formData['nino']);
            if ($validNino === 'PASS') {
                return $view->setTemplate($templates['success']);
            } else {
                return $view->setTemplate($templates['fail']);
            }
        }
        return $view->setTemplate($templates['default']);
    }

    public function processPassportForm(
        Parameters $formData,
        FormInterface $form,
        ViewModel $view,
        array $templates = []
    ): ViewModel {
        $form->setData($formData);
        $validFormat = $form->isValid();

        if ($validFormat) {
            $view->setVariable('passport_data', $formData);
            $validPassport = $this->opgApiService->checkPassportValidity($formData['passport']);
            if ($validPassport === 'PASS') {
                return $view->setTemplate($templates['success']);
            } else {
                return $view->setTemplate($templates['fail']);
            }
        }
        return $view->setTemplate($templates['default']);
    }

    public function processPassportDateForm(
        Parameters $formData,
        FormInterface $form,
        ViewModel $view,
        array $templates = []
    ): ViewModel {
        $expiryDate = sprintf(
            "%s-%s-%s",
            $formData['passport_issued_year'],
            $formData['passport_issued_month'],
            $formData['passport_issued_day']
        );

        $formData->set('passport_date', $expiryDate);

        $form->setData($formData);
        $validDate = $form->isValid();

        if ($validDate) {
            $view->setVariable('valid_date', true);
        } else {
            $view->setVariable('invalid_date', true);
        }
        $view->setVariable('details_open', true);
        return $view->setTemplate($templates['default']);
    }

    public function findLpa(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $form->setData($formData);
        $formArray = $formData->toArray();
        $responseData = [];

        if ($form->isValid()) {
            $responseData = $this->opgApiService->findLpa($uuid, $formArray['lpa']);
        }

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $responseData,
            $templates['default'],
            [
                'lpa_response' => $responseData
            ],
        );
    }

    public function processFindPostOffice(
        string $uuid,
        array $optionsdata,
        FormInterface $form,
        Parameters $formObject,
        array $detailsData
    ): FormProcessorResponseDto {
        $formData = $formObject->toArray();
        $variables = [];

        $variables['next_page'] = $formData['next_page'];

        if ($formData['next_page'] == '2') {
            if ($formData['postcode'] == 'alt') {
                $postcode = $formData['alt_postcode'];
                $form->setData(['postcode', $formData['alt_postcode']]);
            } else {
                $postcode = $formData['postcode'];
                $form->setData(['postcode', $formData['postcode']]);
            }

            if ($form->isValid()) {
                $responseData = $this->opgApiService->listPostOfficesByPostcode($uuid, $postcode);
                $variables['post_office_list'] = $responseData;
            }
        } elseif ($formData['next_page'] == '3') {
            $date = new \DateTime();
            $date->modify("+90 days");
            $deadline = $date->format("d M Y");

            $responseData = $this->opgApiService->getPostOfficeByCode($uuid, (int)$formData['postoffice']);

            $postOfficeAddress = explode(",", $responseData['address']);

            $variables['post_office_summary'] = true;
            $variables['post_office_data'] = $responseData;
            $variables['post_office_address'] = $postOfficeAddress;
            $variables['deadline'] = $deadline;
            $variables['id_method'] = $optionsdata[$detailsData['idMethod']];
        }

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $responseData,
            "",
            $variables
        );
    }
}

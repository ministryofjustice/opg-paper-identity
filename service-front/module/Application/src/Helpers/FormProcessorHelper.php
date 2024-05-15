<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class FormProcessorHelper
{
    public function __construct(private OpgApiServiceInterface $opgApiService)
    {
    }

    public function processDrivingLicenceForm(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $form->setData($formData);
        $validFormat = $form->isValid();
        $variables = [];
        $template = $templates['default'];
        $formArray = $formData->toArray();
        
        if ($validFormat) {
            $variables['dln_data'] = $formData;
            $validDln = $this->opgApiService->checkDlnValidity($formArray['dln']);
            $template = $validDln === 'PASS' ? $templates['success']: $templates['fail'];
        } else {
            $validDln = 'INVALID_FORMAT';
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            ['status' => $validDln],
            $template,
            $variables
        );
    }

    public function processNationalInsuranceNumberForm(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $form->setData($formData);
        $validFormat = $form->isValid();
        $variables = [];
        $template = $templates['default'];

        if ($validFormat) {
            $variables['nino_data'] = $formData;
            $validNino = $this->opgApiService->checkNinoValidity($formData['nino']);

            $template = $validNino === 'PASS' ? $templates['success']: $templates['fail'];
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            [],
            $template,
            $variables
        );
    }

    public function processPassportForm(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $variables = [];
        $template = $templates['default'];
        $form->setData($formData);
        $validFormat = $form->isValid();

        if ($validFormat) {
            $variables['passport_data'] = $formData;
            $validPassport = $this->opgApiService->checkPassportValidity($formData['passport']);

            $template = $validPassport === 'PASS' ? $templates['success']: $templates['fail'];
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            [],
            $template,
            $variables
        );
    }

    public function processPassportDateForm(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $variables = [];
        $template = '';
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
            $variables['valid_date' ] = true;
        } else {
            $variables['invalid_date'] = true;
        }
        $variables['details_open'] = true;
        $template = $templates['default'];
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            [],
            $template,
            $variables
        );
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

<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Application\Services\SiriusApiService;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class FormProcessorHelper
{
    public function __construct(
        private OpgApiServiceInterface $opgApiService
    ) {
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
            $template = $validDln === 'PASS' ? $templates['success'] : $templates['fail'];
            if ($validDln === 'PASS') {
                $this->opgApiService->updateCaseSetDocumentComplete($uuid);
            }
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
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
        $formArray = $formData->toArray();
        $validFormat = $form->isValid();
        $variables = [];
        $template = $templates['default'];

        if ($validFormat) {
            $variables['nino_data'] = $formData;
            $validNino = $this->opgApiService->checkNinoValidity($formArray['nino']);

            $template = $validNino === 'PASS' ? $templates['success'] : $templates['fail'];
            if ($validNino === 'PASS') {
                $this->opgApiService->updateCaseSetDocumentComplete($uuid);
            }
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
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
        $formArray = $formData->toArray();


        if ($validFormat) {
            $variables['passport_data'] = $formData;
            $validPassport = $this->opgApiService->checkPassportValidity($formArray['passport']);

            $template = $validPassport === 'PASS' ? $templates['success'] : $templates['fail'];
            if ($validPassport === 'PASS') {
                $this->opgApiService->updateCaseSetDocumentComplete($uuid);
            }
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
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
            $variables['valid_date'] = true;
        } else {
            $variables['invalid_date'] = true;
        }
        $variables['details_open'] = true;
        $template = $templates['default'];
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $template,
            $variables
        );
    }

    public function processPostOfficeSearchResponse(array $responseData): array
    {
        $locationData = [];
        foreach ($responseData as $key => $array) {
            $jsonKey = json_encode(array_merge($array, ['fad' => $key]));
            $locationData[$jsonKey] = $array;
        }
        return $locationData;
    }
}

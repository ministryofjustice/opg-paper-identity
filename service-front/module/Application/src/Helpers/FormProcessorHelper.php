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

    public function findLpa(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $siriusCheck,
        array $templates = []
    ): FormProcessorResponseDto {
        $form->setData($formData);
        $formArray = $formData->toArray();
        $responseData = [];

        if ($form->isValid()) {
            $opgCheck = $this->opgApiService->findLpa($uuid, $formArray['lpa']);
//            echo json_encode($opgCheck);
//            $nameCheck = $this->compareCpRecords($responseData, $siriusCheck);
            $siriusCheck;
        }

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $templates['default'],
            [
                'lpa_response' => $opgCheck
            ],
        );
    }

    public function stringifyAddresses(array $addresses): array
    {
        $stringified = [];

        foreach ($addresses as $arr) {
            if (array_key_exists('description', $arr)) {
                unset($arr['description']);
            }
            $string = function (array $arr): string {
                $str = "";
                foreach ($arr as $line) {
                    if (strlen($line) > 0) {
                        $str .= $line . ", ";
                    }
                }
                return $str;
            };
            $index = json_encode($arr);

            $stringified[$index] = substr(
                $string($arr),
                0,
                strlen($string($arr)) - 2
            );
        }
        return $stringified;
    }

    public function compareCpRecords(array $opgCheck, array $siriusCheck): array
    {
        $response = [
            'name_match' => false,
            'address_match' => false,
            'error' => false,
            'info' => null
        ];

        try {
            $checkName = $siriusCheck['opg.poas.lpastore']['certificateProvider']['firstNames'] . " " .
                $siriusCheck['opg.poas.lpastore']['certificateProvider']['lastName'];

            $siriusCpAddress = $siriusCheck['opg.poas.lpastore']['certificateProvider']['address'];
            $opgCpAddress = $opgCheck['data']['CP_Address'];

            if (
                $siriusCpAddress['postcode'] == $opgCpAddress['Postcode'] &&
                $siriusCpAddress['line1'] == $opgCpAddress['Line_1'] &&
                $siriusCpAddress['country'] == $opgCpAddress['Country']
            ) {
                $response['address_match'] = true;
            }

            if ($checkName == $opgCheck['data']['CP_Name']) {
                $response['name_match'] = true;
            }
        } catch (\Exception $exception) {
            $response['error'] = true;
            $response['info'] = $exception->getMessage();
        }
        return $response;
    }
}

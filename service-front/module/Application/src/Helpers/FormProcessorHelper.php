<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use Psr\Log\LoggerInterface;

class FormProcessorHelper
{
    public function __construct(
        private LoggerInterface $logger,
        private OpgApiServiceInterface $opgApiService
    ) {
    }

    /**
     * @param FormInterface<array{dln: string, inDate: ?string}> $form
     */
    public function processDrivingLicenceForm(
        string $uuid,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $validFormat = $form->isValid();
        $variables = [];
        $template = $templates['default'];

        if ($validFormat) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

            $variables['dln_data'] = $formArray;
            $variables['validity'] = $this->opgApiService->checkDlnValidity($formArray['dln']);
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $template,
            $variables
        );
    }

    /**
     * @param FormInterface<array{nino: string}> $form
     */
    public function processNationalInsuranceNumberForm(
        string $uuid,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $variables = [];
        $template = $templates['default'];

        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);
            $variables['nino_data'] = $formArray;
            $variables['validity'] = $this->opgApiService->checkNinoValidity($formArray['nino']);
        }

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $template,
            $variables
        );
    }

    /**
     * @param FormInterface<array{passport: string}> $form
     */
    public function processPassportForm(
        string $uuid,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $variables = [];
        $template = $templates['default'];

        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);
            $variables['passport_data'] = $formArray;
            $variables['validity'] = $this->opgApiService->checkPassportValidity($formArray['passport']);
        }
        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $template,
            $variables
        );
    }

    /**
     * @param FormInterface<array{
     *   passport_issued_year: string,
     *   passport_issued_month: string,
     *   passport_issued_day: string,
     *   passport_date?: string
     * }> $form
     */
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

    /**
     * @param FormInterface<array{location: string}> $form
     */
    public function processPostOfficeSearchForm(
        string $uuid,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $variables = [];

        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

            $variables['location'] = $formArray['location'];

            $responseData = $this->opgApiService->listPostOfficesByPostcode($uuid, $formArray['location']);

            $locationData = $this->processPostOfficeSearchResponse($responseData);
            $variables['post_office_list'] = $locationData;
        } else {
            $form->setMessages(['location' => ['Please enter a postcode, town or street name']]);
        }

        $variables['location_form'] = $form;

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $templates['default'],
            $variables,
            null
        );
    }

    /**
     * @param FormInterface<array{postoffice: string}> $form
     */
    public function processPostOfficeSelectForm(
        string $uuid,
        FormInterface $form,
        array $templates = []
    ): FormProcessorResponseDto {
        $redirect = null;

        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

            try {
                $this->opgApiService->addSelectedPostOffice($uuid, $formArray['postoffice']);
                $redirect = 'root/confirm_post_office';
            } catch (OpgApiException) {
                $form->setMessages(['Error saving Post Office to this case.']);
            }
        } else {
            $form->setMessages(['postoffice' => ['Please select an option']]);
        }

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $templates['default'],
            ['form' => $form],
            $redirect
        );
    }

    public function processDateForm(array $params): string
    {
        if (strlen($params['dob_year']) == 2) {
            $year = (int)$params['dob_year'] < 6 ?
                sprintf("20%s", $params['dob_year']) :
                sprintf("19%s", $params['dob_year']);
        } else {
            $year = $params['dob_year'];
        }

        return sprintf(
            "%s-%s-%s",
            $year,
            $params['dob_month'],
            $params['dob_day'],
        );
    }

    /**
     * @param array $fraudCheck
     * @param array $templates
     * @return mixed
     * @throws OpgApiException
     */
    public function processTemplate(array $fraudCheck, array $templates): mixed
    {
        switch ($fraudCheck['decision']) {
            case 'CONTINUE':
            case 'REFER':
            case 'ACCEPT':
            case 'STOP':
                $template = $templates['success'];
                break;
            case 'NODECISION':
                $template = $templates['thin_file'];
                break;
            default:
                $this->logger->error('Fraud check response', [
                    'response' => json_encode($fraudCheck),
                ]);

                throw new OpgApiException('Unknown response received from fraud check service');
        }
        return $template;
    }
}

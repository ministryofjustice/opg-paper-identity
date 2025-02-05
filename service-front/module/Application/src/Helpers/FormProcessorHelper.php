<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Application\Enums\LpaTypes;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\PostOffice\DocumentType;
use Application\Services\SiriusApiService;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class FormProcessorHelper
{
    public function __construct(
        private OpgApiServiceInterface $opgApiService,
        private SiriusApiService $siriusApiService
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

            $variables['post_office_list'] = $this->opgApiService->listPostOfficesByPostcode($uuid, $formArray['location']);
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
        array $templates = [],
        array $detailsData = [],
        array $config = [],
        $request
    ): FormProcessorResponseDto {

        $variables = [];
        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

            try {
                // refactor to only save FAD code
                $selectedPostOffice = $formArray['postoffice']['fad_code'];
                $this->opgApiService->addSelectedPostOffice($uuid, $selectedPostOffice);
                $template = $templates['confirm'];

                $lpaDetails = [];
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $request);

                    if (! empty($lpasData['opg.poas.lpastore'])) {
                        $lpaDetails[$lpa] = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);
                    } else {
                        $lpaDetails[$lpa] = LpaTypes::fromName($lpasData['opg.poas.sirius']['caseSubtype']);
                    }
                }
                $variables['lpa_details'] = $lpaDetails;
                $variables['deadline'] = (new \DateTime($this->opgApiService->estimatePostofficeDeadline($uuid)))->format("d M Y");
                $optionsData = $config['opg_settings']['identity_documents'];

                if (
                    array_key_exists($detailsData['idMethodIncludingNation']['id_method'], $optionsData) &&
                    $detailsData['idMethodIncludingNation']['id_country'] === PostOfficeCountry::GBR->value
                ) {
                    $idMethodForDisplay = $optionsData[$detailsData['idMethodIncludingNation']['id_method']];
                } else {
                    $country = PostOfficeCountry::from($detailsData['idMethodIncludingNation']['id_country'] ?? '');
                    $idMethod = DocumentType::from($detailsData['idMethodIncludingNation']['id_method'] ?? '');
                    $idMethodForDisplay = sprintf('%s (%s)', $idMethod->translate(), $country->translate());
                }

                $postOfficeAddress = explode(',', $formArray['postoffice']['address']);
                $postOfficeAddress[] =  $formArray['postoffice']['post_code'];

                $variables['display_id_method'] = $idMethodForDisplay;
                $variables['post_office_address'] = $postOfficeAddress;
            } catch (OpgApiException) {
                $form->setMessages(['Error saving Post Office to this case.']);
                $template = $templates['default'];
            }
        } else {
            $form->setMessages(['postoffice' => ['Please select an option']]);
            $template = $templates['default'];
        }

        $variable['form'] = $form;

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $template,
            $variables,
        );
    }

    public function processDateForm(array $params): string
    {
        if (
            empty($params['dob_year']) ||
            empty($params['dob_month']) ||
            empty($params['dob_day'])
        ) {
            return '';
        }

        if (strlen($params['dob_year']) == 2) {
            $year = (int)$params['dob_year'] < 6 ?
                sprintf("20%s", $params['dob_year']) :
                sprintf("19%s", $params['dob_year']);
        } else {
            $year = $params['dob_year'];
        }

        return sprintf(
            "%s-%02d-%02d",
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
                throw new OpgApiException('Unknown response received from fraud check service');
        }
        return $template;
    }
}

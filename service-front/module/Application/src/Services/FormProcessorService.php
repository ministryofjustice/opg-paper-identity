<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Stdlib\Parameters;
use Laminas\Form\FormInterface;
use Laminas\View\Model\ViewModel;

class FormProcessorService
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @param OpgApiServiceInterface $opgApiService
     */
    public function __construct(private OpgApiServiceInterface $opgApiService)
    {
    }

    public function processDrivingLicencenForm(
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
        ViewModel $view,
        array $templates = []
    ): ViewModel {
        $form->setData($formData);
        $validLpa = $form->isValid();

        if ($validLpa) {
            $view->setVariable('valid_lpa', true);
            $responseData = $this->opgApiService->findLpa($uuid, $formData->lpa);
//            echo json_encode($responseData);
            $view->setVariable('lpa_response', $responseData);
        } else {
            $view->setVariable('invalid_lpa', true);
        }
        return $view->setTemplate($templates['default']);
    }
}

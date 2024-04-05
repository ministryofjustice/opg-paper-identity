<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Stdlib\Parameters;
use Laminas\Form\Form;
use Laminas\View\Model\ViewModel;

class FormProcessorService
{
    public function __construct(private readonly OpgApiServiceInterface $opgApiService)
    {
    }

    public function processDrivingLicencenForm()
    {
    }

    public function processNationalInsuranceNumberForm(Parameters $formData, Form $form, ViewModel $view, array $templates = []): ViewModel
    {
        $form->setData($formData);

        $validFormat = $form->isValid();

        if ($validFormat) {
            $view->setVariable('nino_data', $formData);
            /**
             * @psalm-suppress InvalidArrayAccess
             */
            $validNino = $this->opgApiService->checkNinoValidity($formData['nino']);
            if ($validNino) {
                return $view->setTemplate($templates['success']);
            } else {
                return $view->setTemplate($templates['fail']);
            }
        }
    }

    public function processPassportForm()
    {
    }
}

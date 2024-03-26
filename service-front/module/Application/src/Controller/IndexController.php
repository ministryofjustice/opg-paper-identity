<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\PassportNumber;
use Application\Forms\PassportDate;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Forms\NationalInsuranceNumber;
use Laminas\Form\Annotation\AttributeBuilder;

class IndexController extends AbstractActionController
{
    protected $plugins;

    public function __construct(private readonly OpgApiServiceInterface $opgApiService)
    {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function donorIdCheckAction(): ViewModel
    {
        $optionsdata = $this->opgApiService->getIdOptionsData();
        $detailsData = $this->opgApiService->getDetailsData();

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/donor_id_check');
    }

    public function donorLpaCheckAction(): ViewModel
    {
        $data = $this->opgApiService->getLpasByDonorData();

        $view = new ViewModel();

        $view->setVariable('data', $data);

        return $view->setTemplate('application/pages/donor_lpa_check');
    }

    public function addressVerificationAction(): ViewModel
    {
        $data = $this->opgApiService->getAddressVerificationData();

        $view = new ViewModel();

        $view->setVariable('options_data', $data);

        return $view->setTemplate('application/pages/address_verification');
    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $view = new ViewModel();

        $form = (new AttributeBuilder())->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $form->setData($formData);
            $validFormat = $form->isValid();

            if ($validFormat) {
                $view->setVariable('nino_data', $formData);
                /**
                 * @psalm-suppress InvalidArrayAccess
                 */
                $validNino = $this->opgApiService->checkNinoValidity($formData['nino']);
                if ($validNino) {
                    return $view->setTemplate('application/pages/national_insurance_number_success');
                } else {
                    return $view->setTemplate('application/pages/national_insurance_number_fail');
                }
            }
        }

        return $view->setTemplate('application/pages/national_insurance_number');
    }

    public function drivingLicenceNumberAction(): ViewModel
    {
        $view = new ViewModel();

        $form = (new AttributeBuilder())->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $form->setData($formData);
            $validFormat = $form->isValid();

            if ($validFormat) {
                $view->setVariable('dln_data', $formData);
                /**
                 * @psalm-suppress InvalidArrayAccess
                 */
                $validDln = $this->opgApiService->checkDlnValidity($formData['dln']);
                if ($validDln) {
                    return $view->setTemplate('application/pages/driving_licence_number_success');
                } else {
                    return $view->setTemplate('application/pages/driving_licence_number_fail');
                }
            }
        }

        return $view->setTemplate('application/pages/driving_licence_number');
    }

    public function passportNumberAction(): ViewModel
    {
        $view = new ViewModel();

        $form = (new AttributeBuilder())->createForm(PassportNumber::class);
        $dateSubForm = (new AttributeBuilder())->createForm(PassportDate::class);
        $detailsData = $this->opgApiService->getDetailsData();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);
        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('details_open', false);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();

            if (array_key_exists('check_button', $formData->toArray())) {
                $data = $formData->toArray();

                $expiryDate = sprintf(
                    "%s-%s-%s",
                    $data['passport_issued_year'],
                    $data['passport_issued_month'],
                    $data['passport_issued_day']
                );

                $formData->set('passport_date', $expiryDate);

                $dateSubForm->setData($formData);
                $validDate = $dateSubForm->isValid();

                if ($validDate) {
                    $view->setVariable('valid_date', true);
                } else {
                    $view->setVariable('invalid_date', true);
                }
                $view->setVariable('details_open', true);
                $form->setData($formData);
            } else {
                $form->setData($formData);
                $validFormat = $form->isValid();

                if ($validFormat) {
                    $view->setVariable('passport_data', $formData);
                    /**
                     * @psalm-suppress InvalidArrayAccess
                     */
                    $validPassport = $this->opgApiService->checkPassportValidity($formData['passport']);
                    if ($validPassport) {
                        return $view->setTemplate('application/pages/passport_number_success');
                    } else {
                        return $view->setTemplate('application/pages/passport_number_fail');
                    }
                }
            }
        }

        return $view->setTemplate('application/pages/passport_number');
    }

    public function howWillDonorConfirmAction(): ViewModel
    {
        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();

            switch ($formData['id_method']) {
                case 'Passport':

                    break;

                case 'Driving Licence':

                    break;

                case 'National Insurance Number':

                    break;
            }
        }


        $optionsdata = $this->opgApiService->getIdOptionsData();
        $detailsData = $this->opgApiService->getDetailsData();

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/how_will_the_donor_confirm');
    }
}

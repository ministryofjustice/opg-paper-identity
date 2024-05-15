<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\IdQuestions;
use Application\Forms\PassportNumber;
use Application\Forms\PassportDate;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Annotation\AttributeBuilder;
use Application\Forms\NationalInsuranceNumber;

class IndexController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
    ) {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function startAction(): Response
    {
        $lpasQuery = $this->params()->fromQuery("lpas");
        $lpas = [];
        foreach ($lpasQuery as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data['opg.poas.lpastore'];
        }

        $type = $this->params()->fromQuery("personType");
        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        $detailsData = $this->processLpaResponse($type, $lpas[0]);
        // Find the details of the actor (donor or certificate provider, based on URL) that we need to ID check them

        // Create a case in the API with the LPA UID and the actors' details

        // Redirect to the "select which ID to use" page for this case

        $case = $this->opgApiService->createCase(
            $detailsData['first_name'],
            $detailsData['last_name'],
            $detailsData['dob'],
            $type,
            $lpasQuery,
            $detailsData['address']
        );

//        die(json_encode($case));

        return $type === 'donor' ?
            $this->redirect()->toRoute('root/how_donor_confirms', ['uuid' => $case['uuid']]) :
            $this->redirect()->toRoute('root/cp_how_cp_confirms', ['uuid' => $case['uuid']]);
    }

    private function processLpaResponse(string $type, array $data): array
    {
        $parsedIdentity = [];

        if ($type === 'donor') {
            $address = $this->processAddress($type, $data['donor']['address']);
            $parsedIdentity['first_name'] = $data['donor']['firstNames'];
            $parsedIdentity['last_name'] = $data['donor']['lastName'];
            $parsedIdentity['dob'] = (new \DateTime($data['donor']['dateOfBirth']))->format("Y-m-d");
            $parsedIdentity['address'] = $address;
        } else {
            $address = $this->processAddress($type, $data['certificateProvider']['address']);
            $parsedIdentity['first_name'] = $data['certificateProvider']['firstNames'];
            $parsedIdentity['last_name'] = $data['certificateProvider']['lastName'];
            $parsedIdentity['dob'] = '1999-01-01'; //temp setting should be null in prod
            $parsedIdentity['address'] = $address;
        }
//        die(json_encode($parsedIdentity));
        return $parsedIdentity;
    }

    private function processAddress(string $type, array $siriusAddress): array
    {
        $address = [];

        foreach ($siriusAddress as $line) {
            $address[] = $line;
        }

//        if ($type === 'donor') {
//            $address[] = $siriusAddress['line1'];
//            $address[] = $siriusAddress['line2'];
////            $address['town'] = $siriusAddress['town'];
//            $address[] = $siriusAddress['postcode'];
//            $address[] = $siriusAddress['country'];
//        } else {
//            $address[] = $siriusAddress['line1'];
//            $address[] = $siriusAddress['line2'];
//            $address[] = $siriusAddress['line3'];
////            $address['postcode'] = $siriusAddress['postcode'];
//            $address[] = $siriusAddress['country'];
//        }
        return $address;
    }
}

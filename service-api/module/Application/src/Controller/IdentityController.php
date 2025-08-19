<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Aws\Secrets\AwsSecret;
use Application\DrivingLicence\ValidatorInterface as LicenceValidatorInterface;
use Application\DWP\DwpApi\DwpApiException;
use Application\DWP\DwpApi\DwpApiService;
use Application\Enums\DocumentType;
use Application\Exceptions\NotImplementedException;
use Application\Experian\Crosscore\FraudApi\DTO\AddressDTO;
use Application\Experian\Crosscore\FraudApi\DTO\RequestDTO;
use Application\Experian\Crosscore\FraudApi\FraudApiException;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\HMPO\HmpoApi\HmpoApiService;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CaseProgress;
use Application\Model\Entity\DocCheck;
use Application\Model\Entity\FraudScore;
use Application\Model\Entity\IdMethod;
use Application\Model\Entity\Problem;
use Application\Nino\ValidatorInterface;
use Application\Sirius\UpdateStatus;
use Application\View\JsonModel;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress InvalidArgument
 * @see https://github.com/laminas/laminas-view/issues/239
 */
class IdentityController extends AbstractActionController
{
    public function __construct(
        private readonly DwpApiService $dwpApiService,
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly DataWriteHandler $dataHandler,
        private readonly LicenceValidatorInterface $licenceValidator,
        private readonly HmpoApiService $hmpoApiService,
        private readonly LoggerInterface $logger,
        private readonly FraudApiService $experianCrosscoreFraudApiService,
        private readonly CaseOutcomeCalculator $caseOutcomeCalculator,
    ) {
    }

    public function indexAction(): JsonModel
    {
        return new JsonModel();
    }

    public function createAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        $caseData = CaseData::fromArray($data);

        $validator = (new AttributeBuilder())
            ->createForm($caseData)
            ->setData(get_object_vars($caseData));

        if ($validator->isValid()) {
            $caseData->id = strval(Uuid::uuid4());

            $this->dataHandler->insertUpdateData($caseData);

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

            return new JsonModel(['uuid' => $caseData->id]);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

        return new JsonModel(new Problem(
            'Invalid data',
            extra: ['errors' => $validator->getMessages()],
        ));
    }

    public function updateAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        $uuid = $this->params()->fromRoute('uuid');

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $case) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
            return new JsonModel(new Problem('Case not found'));
        }

        $case->update($data);

        $validator = (new AttributeBuilder())
            ->createForm($case)
            ->setData(get_object_vars($case));

        if ($validator->isValid()) {
            $this->dataHandler->insertUpdateData($case);

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

            return new JsonModel(['case' => $case->toArray()]);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

        return new JsonModel(new Problem(
            'Invalid data',
            extra: ['errors' => $validator->getMessages()],
        ));
    }

    public function detailsAction(): JsonModel
    {
        /** @var string $uuid */
        $uuid = $this->getRequest()->getQuery('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing uuid'));
        }

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        if (! empty($case)) {
            return new JsonModel($case);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);

        return new JsonModel(new Problem('Case not found'));
    }

    /**
     * @throws DwpApiException
     */
    public function validateNinoAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
            return new JsonModel(new Problem('Case not found'));
        }

        $correlationUuid = Uuid::uuid4()->toString();
        $idMethodData = $caseData->idMethod?->jsonSerialize();
        $idMethodData['dwpIdCorrelation'] = $correlationUuid;

        $this->dataHandler->updateCaseData(
            $uuid,
            'idMethod',
            $idMethodData
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        $dwpResponse = $this->dwpApiService->validateNino($caseData, $data['nino'], $correlationUuid);

        if ($dwpResponse === 'MULTIPLE_MATCH') {
            $this->logger->info($dwpResponse);
            $caseProgress = $caseData->caseProgress ?? new CaseProgress();

            $caseProgress->restrictedMethods[] = DocumentType::NationalInsuranceNumber->value;

            $this->dataHandler->updateCaseData(
                $uuid,
                'caseProgress',
                $caseProgress,
            );
        }

        return new JsonModel([
            'result' => $dwpResponse,
        ]);
    }

    public function validateDrivingLicenceAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $licenceStatus = $this->licenceValidator->validateDrivingLicence($data['dln']);

        $response = [
            'status' => $licenceStatus,
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function validatePassportAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
            return new JsonModel(new Problem('Case not found'));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        $hmpoResponse = $this->hmpoApiService->validatePassport($caseData, intval($data['passportNumber']));

        return new JsonModel([
            'result' => $hmpoResponse,
        ]);
    }

    public function updateIdMethodAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem("Missing UUID"));
        }

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $case) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
            return new JsonModel(new Problem('Case not found'));
        }

        if (! is_null($case->idMethod)) {
            $idMethod = $case->idMethod;
        } else {
            $idMethod = new IdMethod();
        }

        $idMethod->update($data);

        $this->dataHandler->updateCaseData(
            $uuid,
            'idMethod',
            $idMethod
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel(['result' => 'Updated idMethod']);
    }

    public function addSelectedPostofficeAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem('Missing UUID'));
        }
        $counterServiceMap = [
            "selectedPostOffice" => $data['selected_postoffice'],
        ];

        $this->dataHandler->updateCaseData(
            $uuid,
            'counterService',
            $counterServiceMap,
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel(['result' => 'Updated selected_postoffice']);
    }

    public function addCaseLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];

        /**
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $lpas = $data->lpas;
        if (! in_array($lpa, $lpas)) {
            $lpas[] = $lpa;
            $this->dataHandler->updateCaseData(
                $uuid,
                'lpas',
                $lpas
            );
            $response['result'] = "Updated";
        } else {
            $response['result'] = "LPA is already added to this case";
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function removeCaseLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];

        /**
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $lpas = $data->lpas;
        if (in_array($lpa, $lpas)) {
            $keptLpas = [];                 //this is inelegant but works, while popping the
            foreach ($lpas as $keptLpa) {   //value out of the existing array breaks the code
                if ($keptLpa !== $lpa) {
                    $keptLpas[] = $keptLpa;
                }
            }
            $this->dataHandler->updateCaseData(
                $uuid,
                'lpas',
                $keptLpas
            );
            $response['result'] = "Removed";
        } else {
            $response['result'] = "LPA is not added to this case";
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function saveAddressToCaseAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('missing UUID'));
        }

        $this->dataHandler->updateCaseData(
            $uuid,
            'claimedIdentity.address',
            $data,
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel(['result' => 'Updated']);
    }

    public function saveProfessionalAddressToCaseAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('missing UUID'));
        }
        $this->dataHandler->updateCaseData(
            $uuid,
            'claimedIdentity.professionalAddress',
            $data,
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel(['result' => 'Updated professionalAddress']);
    }

    public function setDocumentCompleteAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $state = array_key_exists('state', $data) ? $data['state'] : true;

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('missing UUID'));
        }

        if (! $data['idDocument']) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('idDocument not set'));
        }

        /** @var CaseData $caseData */
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);
        $caseProgress = $caseData->caseProgress ?? new CaseProgress();

        $caseProgress->docCheck = DocCheck::fromArray([
            'idDocument' => $data['idDocument'],
            'state' => $state
        ]);

        $this->dataHandler->updateCaseData(
            $uuid,
            'caseProgress',
            $caseProgress
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel(['result' => 'Update caseProgress']);
    }

    public function updateDobAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $dob = $this->params()->fromRoute('dob');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('missing UUID'));
        }

        if (! $dob) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('dob not set'));
        }

        $this->dataHandler->updateCaseData(
            $uuid,
            'claimedIdentity.dob',
            $dob
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel(['result' => 'Updated dob']);
    }

    public function updateNameAction(): JsonModel
    {
        $firstName = $this->params()->fromQuery("firstName");
        $lastName = $this->params()->fromQuery("lastName");
        $uuid = $this->params()->fromRoute('uuid');
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $response = new Problem('Missing UUID');
        } elseif (! $firstName) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $response = new Problem('Missing First Name');
        } elseif (! $lastName) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $response = new Problem('Missing Last Name');
        }

        if (! $response) {
            $this->dataHandler->updateCaseData(
                $uuid,
                'claimedIdentity.firstName',
                $firstName
            );
            $this->dataHandler->updateCaseData(
                $uuid,
                'claimedIdentity.lastName',
                $lastName
            );

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            $response = ['result' => 'Updated'];
        }

        return new JsonModel($response);
    }

    public function saveCaseProgressAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode(
            $this->getRequest()->getContent(),
            true
        );

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem("Missing UUID"));
        }

        $this->dataHandler->updateCaseData(
            $uuid,
            'caseProgress',
            $data,
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel(['result' => "Progress recorded for {$uuid}"]);
    }

    /**
     * @throws FraudApiException
     * @throws GuzzleException
     */
    public function requestFraudCheckAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing uuid'));
        }

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $case) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Case does not exist'));
        }

        if (! $case->claimedIdentity) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Case does not have claimed identity'));
        }

        if (! $case->claimedIdentity->address) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Case does not have an associated address'));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        $addressDto = new AddressDTO(
            $case->claimedIdentity->address['line1'],
            $case->claimedIdentity->address['line2'] ?? "",
            $case->claimedIdentity->address['line3'] ?? "",
            $case->claimedIdentity->address['town'] ?? "",
            $case->claimedIdentity->address['postcode'],
            $case->claimedIdentity->address['country'] ?? "",
        );

        $dto = new RequestDTO(
            $case->claimedIdentity->firstName,
            $case->claimedIdentity->lastName,
            $case->claimedIdentity->dob,
            $addressDto
        );

        $response = $this->experianCrosscoreFraudApiService->getFraudScore($dto);

        $caseProgress = $case->caseProgress ?? new CaseProgress();

        $caseProgress->fraudScore = FraudScore::fromArray($response->toArray());

        $this->dataHandler->updateCaseData(
            $uuid,
            'caseProgress',
            $caseProgress,
        );

        return new JsonModel($response->toArray());
    }

    public function saveCaseAssistanceAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode(
            $this->getRequest()->getContent(),
            true
        );

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem("Missing UUID"));
        }

        $this->dataHandler->updateCaseData(
            $uuid,
            'caseAssistance',
            $data,
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel(['result' => "Case Assistance recorded for {$uuid}"]);
    }

    public function sendIdentityCheckAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');

        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid ?? '');
        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
            return new JsonModel(new Problem('Case not found'));
        }

        $this->caseOutcomeCalculator->updateSendIdentityCheck($caseData);

        return new JsonModel();
    }
}

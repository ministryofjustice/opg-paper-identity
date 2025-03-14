<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Aws\Secrets\AwsSecret;
use Application\DrivingLicense\ValidatorInterface as LicenseValidatorInterface;
use Application\DWP\DwpApi\DwpApiException;
use Application\DWP\DwpApi\DwpApiService;
use Application\Exceptions\NotImplementedException;
use Application\Experian\Crosscore\FraudApi\DTO\AddressDTO;
use Application\Experian\Crosscore\FraudApi\DTO\RequestDTO;
use Application\Experian\Crosscore\FraudApi\FraudApiException;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CaseProgress;
use Application\Model\Entity\DocCheck;
use Application\Model\Entity\FraudScore;
use Application\Model\Entity\Problem;
use Application\Nino\ValidatorInterface;
use Application\Passport\ValidatorInterface as PassportValidator;
use Application\Sirius\EventSender;
use Application\Sirius\UpdateStatus;
use Application\View\JsonModel;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Psr\Clock\ClockInterface;
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
        private readonly LicenseValidatorInterface $licenseValidator,
        private readonly PassportValidator $passportService,
        private readonly LoggerInterface $logger,
        private readonly FraudApiService $experianCrosscoreFraudApiService,
        private readonly EventSender $eventSender,
        private readonly ClockInterface $clock,
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

            try {
                $this->dataHandler->insertUpdateData($caseData);
            } catch (\Exception $exception) {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

                return new JsonModel(new Problem($exception->getMessage()));
            }

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

        try {
            $case->update($data);
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem($exception->getMessage()));
        }

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

        try {
            $correlationUuid = Uuid::uuid4()->toString();
            $idMethodData = $caseData?->idMethodIncludingNation?->jsonSerialize();
            $idMethodData['dwp_id_correlation'] = $correlationUuid;

            $this->dataHandler->updateCaseData(
                $uuid,
                'idMethodIncludingNation',
                $idMethodData
            );

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

            if ($this->dwpApiService->validateNino($caseData, $data['nino'], $correlationUuid)) {
                return new JsonModel([
                    'result' => 'PASS',
                ]);
            } else {
                return new JsonModel([
                    'result' => 'NO_MATCH',
                ]);
            }
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }
    }

    public function validateDrivingLicenceAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $licenseStatus = $this->licenseValidator->validateDrivingLicense($data['dln']);

        $response = [
            'status' => $licenseStatus,
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function validatePassportAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $passportStatus = $this->passportService->validatePassport(intval($data['passport']));

        $response = [
            'status' => $passportStatus,
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function updatedMethodAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem("Missing UUID"));
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'idMethod',
                $data['idMethod']
            );
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function addSelectedPostofficeAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem('Missing UUID'));
        }
        $counterServiceMap = [
            "selectedPostOffice" => $data['selected_postoffice'],
        ];

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'counterService',
                $counterServiceMap,
            );
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function addCaseLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];
        $status = Response::STATUS_CODE_200;

        try {
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
        } catch (\Exception $exception) {
            $status = Response::STATUS_CODE_400;
            $response['exception'] = $exception->getMessage();
        }

        $this->getResponse()->setStatusCode($status);

        return new JsonModel($response);
    }

    public function removeCaseLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];
        $status = Response::STATUS_CODE_200;

        try {
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
        } catch (\Exception $exception) {
            $status = Response::STATUS_CODE_400;
            $response = new Problem('Cannot remove LPA from case', extra: ['exception' => $exception->getMessage()]);
        }

        $this->getResponse()->setStatusCode($status);

        return new JsonModel($response);
    }

    public function saveAddressToCaseAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID",
            ];

            return new JsonModel($response);
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'claimedIdentity.address',
                $data,
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();

            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function saveProfessionalAddressToCaseAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID",
            ];

            return new JsonModel($response);
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'claimedIdentity.professionalAddress',
                $data,
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();

            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function setDocumentCompleteAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;
        $state = array_key_exists('state', $data) ? $data['state'] : true;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID",
            ];

            return new JsonModel($response);
        }

        if (! $data['idDocument']) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing idDocument",
            ];

            return new JsonModel($response);
        }

        /** @var CaseData $caseData */
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);
        $caseProgress = $caseData->caseProgress ?? new CaseProgress();

        $caseProgress->docCheck = DocCheck::fromArray([
            'idDocument' => $data['idDocument'],
            'state' => $state
        ]);

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'caseProgress',
                $caseProgress
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();

            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function updateDobAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $dob = $this->params()->fromRoute('dob');
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID",
            ];

            return new JsonModel($response);
        }

        if (! $dob) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing Date of Birth",
            ];

            return new JsonModel($response);
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'claimedIdentity.dob',
                $dob
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();

            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function updateNameAction(): JsonModel
    {
        $firstName = $this->params()->fromQuery("firstName");
        $lastName = $this->params()->fromQuery("lastName");
        $uuid = $this->params()->fromRoute('uuid');
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID",
            ];
        } elseif (! $firstName) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing First Name",
            ];
        } elseif (! $lastName) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing Last Name",
            ];
        }

        if (! $response) {
            try {
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
            } catch (\Exception $exception) {
                $response['result'] = "Not Updated";
                $response['error'] = $exception->getMessage();
            }

            $this->getResponse()->setStatusCode($status);
            $response['result'] = "Updated";
        }

        return new JsonModel($response);
    }

    public function updateCpPoIdAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem("Missing UUID"));
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'idMethodIncludingNation',
                $data,
            );
            $this->dataHandler->updateCaseData(
                $uuid,
                'idRoute',
                $data['id_route'],
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function saveCaseProgressAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode(
            $this->getRequest()->getContent(),
            true
        );

        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem("Missing UUID"));
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'caseProgress',
                $data,
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Progress recorded at " . $uuid . '/' . $data['last_page'];

        return new JsonModel($response);
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

        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem("Missing UUID"));
        }

        try {
            $this->dataHandler->updateCaseData(
                $uuid,
                'caseAssistance',
                $data,
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);

            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Progress recorded at " . $uuid . '/' . $data['last_page'];

        return new JsonModel($response);
    }

    public function sendSiriusEventAction(): JsonModel
    {
        $statusLookup = [
            'abandon-case' => UpdateStatus::Exit->value,
            'cop-started' => UpdateStatus::CopStarted->value,
            'vouch-started' => UpdateStatus::VouchStarted->value
        ];

        $uuid = $this->params()->fromRoute('uuid');
        $status = $this->params()->fromRoute('status');

        if (! array_key_exists($status, $statusLookup)) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem('Invalid case status'));
        }

        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid ?? '');

        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);

            return new JsonModel(new Problem('Case not found'));
        }

        $this->eventSender->send("identity-check-updated", [
            "reference" => "opg:" . $caseData->id,
            "actorType" => $caseData->personType,
            "lpaUids" => $caseData->lpas,
            "time" => $this->clock->now()->format('c'),
            "state" => $statusLookup[$status],
        ]);

        $this->dataHandler->setTTL($uuid);

        return new JsonModel();
    }
}

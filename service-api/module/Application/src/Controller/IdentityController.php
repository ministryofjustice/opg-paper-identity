<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Nino\ValidatorInterface;
use Application\DrivingLicense\ValidatorInterface as LicenseValidatorInterface;
use Application\Passport\ValidatorInterface as PassportValidator;
use Application\KBV\KBVServiceInterface;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Aws\DynamoDb\Marshaler;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
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
        private readonly ValidatorInterface $ninoService,
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly DataImportHandler $dataImportHandler,
        private readonly LicenseValidatorInterface $licenseValidator,
        private readonly PassportValidator $passportService,
        private readonly KBVServiceInterface $KBVService
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

        if ($caseData->isValid()) {
            $uuid = Uuid::uuid4();
            $item = [
                'id'            => ['S' => $uuid->toString()],
                'personType'     => ['S' => $data["personType"]],
                'firstName'     => ['S' => $data["firstName"]],
                'lastName'      => ['S' => $data["lastName"]],
                'dob'           => ['S' => $data["dob"]],
                'lpas'          => ['SS' => $data['lpas']]
            ];

            $this->dataImportHandler->insertData('cases', $item);

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            return new JsonModel(['uuid' => $uuid]);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

        return new JsonModel(['error' => 'Invalid data']);
    }

    public function detailsAction(): JsonModel
    {
        /** @var string $uuid */
        $uuid = $this->getRequest()->getQuery('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }

        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        if (! empty($data)) {
            return new JsonModel($data);
        }

        return new JsonModel(['error' => 'Invalid uuid']);
    }

    public function testdataAction(): JsonModel
    {
        $this->dataImportHandler->load();
        $data = $this->dataQueryHandler->returnAll('cases');

        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function findByNameAction(): JsonModel
    {
        /** @var string $name */
        $name = $this->getRequest()->getQuery('username');
        $data = $this->dataQueryHandler->queryByName($name);
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function findByIdNumberAction(): JsonModel
    {
        /** @var string $id */
        $id = $this->getRequest()->getQuery('id');
        $data = $this->dataQueryHandler->queryByIDNumber($id);
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function addressVerificationAction(): JsonModel
    {
        $data = [
            'Passport',
            'Driving Licence',
            'National Insurance Number',
            'Voucher',
            'Post Office',
        ];

        return new JsonModel($data);
    }

    public function listLpasAction(): JsonModel
    {
        $data = [
            [
                'lpa_ref' => 'PW M-1234-ABCD-AAAA',
                'donor_name' => 'Mary Anne Chapman'
            ],
//            [
//                'lpa_ref' => 'PA M-1234-ABCD-XXXX',
//                'donor_name' => 'Mary Anne Chapman'
//            ]
        ];

        return new JsonModel($data);
    }

    public function verifyNinoAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        $ninoStatus = $this->ninoService->validateNINO($data['nino']);

        $response = [
            'status' => $ninoStatus
        ];

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function validateDrivingLicenceAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $licenseStatus = $this->licenseValidator->validateDrivingLicense($data['dln']);

        $response = [
            'status' => $licenseStatus
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function validatePassportAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $passportStatus = $this->passportService->validatePassport(intval($data['passport']));

        $response = [
            'status' => $passportStatus
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function getKbvQuestionsAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (array_key_exists('kbvQuestions', $case[0])) {
            $questions = $case[0]['kbvQuestions'];
 
            //revisit formatting here, output needs to be array
            return new JsonModel($questions);
        } else {
            $questions = $this->KBVService->getKBVQuestions();
            //update formatting here to match FE expected
            $formattedQuestions = [];
            $mapNumber = [
                '0' => 'one',
                '1' => 'two',
                '2' => 'three',
                '3' => 'four'
            ];
            for ($i=0; $i < 4; $i++) {
                $question = $questions[$i];
                $number = $mapNumber[$i];
                $questionNumbered = array_merge(['number' => $number], $question);
                $formattedQuestions[$number] = $questionNumbered;
            }

            $marshal = new Marshaler();
            // no save the questions against the case id, is there a way to store $questions as array?
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'kbvQuestions',
                'S',
                json_encode($formattedQuestions)
            );
        }

        if (!$uuid) {
            /**
             * @psalm-suppress PossiblyUndefinedVariable
             */
            $response[$uuid] = [
                "error" => "thin_file_error"
            ];
            return new JsonModel($response[$uuid]);
        }

        /**
         * @psalm-suppress PossiblyUndefinedVariable
         */
        $response[$uuid] = [
            "one" => [
                "question" => "Who provides your mortgage?",
                "number" => "one",
                "prompts" => [
                    0 => "Nationwide",
                    1 => "Halifax",
                    2 => "Lloyds",
                    3 => "HSBC",
                ]
            ],
            "two" => [
                "question" => "Who provides your personal mobile contract?",
                "number" => "two",
                "prompts" => [
                    0 => "EE",
                    1 => "Vodafone",
                    2 => "BT",
                    3 => "iMobile",
                ]
            ],
            "three" => [
                "question" => "What are the first two letters of the last name of another 
                person on the electoral register at your address?",
                "number" => "three",
                "prompts" => [
                    0 => "Ka",
                    1 => "Ch",
                    2 => "Jo",
                    3 => "None of the above",
                ]
            ],
            "four" => [
                "question" => "Who provides your current account?",
                "number" => "four",
                "prompts" => [
                    0 => "Santander",
                    1 => "HSBC",
                    2 => "Halifax",
                    3 => "Nationwide",
                ]
            ]
        ];

        return new JsonModel($formattedQuestions);
    }

    public function checkKbvAnswersAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');

        $answers = [];
        $data = $this->getRequest()->getPost();
        $result = 'pass';

        $answers[$uuid] = [
            "one" => "Nationwide",
            "two" => "EE",
            "three" => "Ka",
            "four" => "Santander",
        ];

        foreach ($data['answers'] as $key => $value) {
            if ($value != $answers[$uuid][$key]) {
                $result = 'fail';
            }
        }

        /**
         * @psalm-suppress PossiblyUndefinedVariable
         */
        $response['result'] = $result;

        return new JsonModel($response);
    }
}

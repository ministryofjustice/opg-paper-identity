<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IdentityController;
use Application\Fixtures\DataQueryHandler;
use Application\KBV\KBVServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class IdentityControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private KBVServiceInterface&MockObject $KBVServiceMock;
    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->KBVServiceMock = $this->createMock(KBVServiceInterface::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(KBVServiceInterface::class, $this->KBVServiceMock);
    }

    public function testIndexActionResponse(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertEquals('{"Laminas":"Paper ID Service API"}', $this->getResponse()->getContent());
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->jsonHeaders();

        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }

    public function jsonHeaders(): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
    }

    public function testDetailsWithUUID(): void
    {
        $this->dispatch('/identity/details?uuid=2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    public function testDetailsWithNoUUID(): void
    {
        $response = '{"status":400,"type":"HTTP400","title":"Bad Request"}';
        $this->dispatch('/identity/details', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    /**
     * @param array $case
     * @param int $status
     * @return void
     * @dataProvider caseData
     */
    public function testCreate(array $case, int $status): void
    {
        $this->dispatchJSON(
            '/identity/create',
            'POST',
            $case
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('create_case');
    }

    public static function caseData(): array
    {
        $validData = [
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'personType' => 'donor',
            'dob'   => '1980-10-10',
            'lpas' => [
                'M-XYXY-YAGA-35G3',
                'M-VGAS-OAGA-34G9'
            ]
        ];

        return [
            [$validData, Response::STATUS_CODE_200],
            [array_merge($validData, ['lastName' => '']), Response::STATUS_CODE_400],
            [array_merge($validData, ['dob' => '11-11-2020']), Response::STATUS_CODE_400],
            [array_replace_recursive($validData, ['lpas' => ['NAHF-AHDA-NNN']]), Response::STATUS_CODE_400],
        ];
    }

    /**
     * @dataProvider kbvAnswersData
     */
    public function testKbvAnswers(string $uuid, array $provided, array $actual, string $result, int $status): void
    {
        if ($result !== 'error') {
            $this->dataQueryHandlerMock
                ->expects($this->once())->method('getCaseByUUID')
                ->with($uuid)
                ->willReturn($actual);
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/kbv-answers',
            'POST',
            $provided
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('check_kbv_answers');

        if ($result === "error") {
            $this->assertEquals(
                '{"status":400,"type":"HTTP400","title":"Bad Request"}',
                $this->getResponse()->getContent()
            )
            ;
        } else {
            $this->assertEquals('{"result":"' . $result . '"}', $this->getResponse()->getContent());
        }
    }

    public static function kbvAnswersData(): array
    {
        $uuid = 'e32a4d31-f15b-43f8-9e21-2fb09c8f45e7';
        $invalidUUID = 'asdkfh3984ahksdjka';
        $provided = [
            'answers' => [
                'one' => 'VoltWave',
                'two' => 'Germanotta',
                'tree' => 'July',
                'four' => 'Pink'
            ]
        ];
        $providedIncomplete = $provided;
        unset($providedIncomplete['answers']['four']);

        $providedIncorrect = $provided;
        $providedIncorrect['answers']['two'] = 'incorrect answer';

        $actual = [
            '0' => [
                'kbvQuestions' => json_encode([
                    'one' => ['answer' => 'VoltWave'],
                    'two' => ['answer' => 'Germanotta'],
                    'tree' => ['answer' => 'July'],
                    'four' => ['answer' => 'Pink']
                ])
            ]
        ];

        return [
            [$uuid, $provided, $actual, 'pass', Response::STATUS_CODE_200],
            [$uuid, $providedIncomplete, $actual, 'fail', Response::STATUS_CODE_200],
            [$uuid, $providedIncorrect, $actual, 'fail', Response::STATUS_CODE_200],
            [$invalidUUID, $provided, $actual, 'error', Response::STATUS_CODE_400],
        ];
    }

    public function testKBVQuestionsWithNoUUID(): void
    {
        $response = '{"status":400,"type":"HTTP400","title":"Bad Request"}';
        $this->dispatch('/cases/kbv-questions', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithVerifiedDocsCaseGeneratesQuestions(): void
    {
        $caseData = [
            'id' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
            'firstName' => 'test',
            'lastName' => 'name',
            'documentComplete' => true,
        ];
        $formattedQuestions = $this->formattedQuestions();

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn([0 => $caseData]);

        $this->KBVServiceMock
            ->expects($this->once())->method('fetchFormattedQuestions')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($formattedQuestions);

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('Who is your electricity supplier?', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }
    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithVerifiedDocsCaseAndExistingQuestions(): void
    {
        $caseData = [
            'id' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
            'firstName' => 'test',
            'lastName' => 'name',
            'documentComplete' => true,
            'kbvQuestions' => json_encode($this->formattedQuestions())
        ];

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn([0 => $caseData]);

        $this->KBVServiceMock
            ->expects($this->never())->method('fetchFormattedQuestions')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999');

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('Who is your electricity supplier?', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithUnVerifiedDocsCase(): void
    {
        $response = '{"error":"Document checks incomplete or unable to locate case"}';
        $caseData = [
            'id' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
            'firstName' => 'test',
            'lastName' => 'name',
            'documentComplete' => false,
        ];

        $this->dataQueryHandlerMock->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn([0 => $caseData]);

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @dataProvider ninoData
     */
    public function testNino(string $nino, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_nino',
            'POST',
            ['nino' => $nino]
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_nino');
    }

    public static function ninoData(): array
    {
        return [
            ['AA112233A', 'PASS', Response::STATUS_CODE_200],
            ['BB112233A', 'PASS', Response::STATUS_CODE_200],
            ['AA112233D', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
            ['AA112233C', 'NO_MATCH', Response::STATUS_CODE_200]
        ];
    }

    /**
     * @dataProvider drivingLicenceData
     */
    public function testDrivingLicence(string $drivingLicenceNo, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_driving_licence',
            'POST',
            ['dln' => $drivingLicenceNo]
        );
        $this->assertResponseStatusCode($status);
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_driving_licence');
    }

    public static function drivingLicenceData(): array
    {
        return [
            ['CHAPM301534MA9AY', 'PASS', Response::STATUS_CODE_200],
            ['SMITH710238HA3DY', 'PASS', Response::STATUS_CODE_200],
            ['SMITH720238HA3D8', 'NO_MATCH', Response::STATUS_CODE_200],
            ['JONES630536AB3J9', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200]
        ];
    }

    /**
     * @dataProvider passportData
     */
    public function testPassportNumber(int $passportNumber, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_passport',
            'POST',
            ['passport' => $passportNumber]
        );
        $this->assertResponseStatusCode($status);
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_passport');
    }

    public static function passportData(): array
    {
        return [
            [123456788, 'NO_MATCH', Response::STATUS_CODE_200],
            [123456789, 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
            [123333456, 'PASS', Response::STATUS_CODE_200],
            [123456784, 'PASS', Response::STATUS_CODE_200],
        ];
    }

    public function dispatchJSON(string $path, string $method, mixed $data = null): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(is_string($data) ? $data : json_encode($data));

        $this->dispatch($path, $method);
    }

    public function formattedQuestions(): array
    {
        return [
            'formattedQuestions' => [
                'one' => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power'
                    ],
                    'answer' => 'VoltWave'
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25"
                    ],
                    'answer' => "£5.99"
                ]
            ],
            'questionsWithoutAnswers' => [
                'one' => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power'
                    ]
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25"
                    ]
                ]
            ],
        ];
    }
}

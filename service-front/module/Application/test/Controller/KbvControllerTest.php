<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\KbvController;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class KbvControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
    }

    /**
     * @dataProvider personTypeDataProvider
     */
    public function testKbvQuestionsFormRenders(string $personType): void
    {
        $mockResponseData = [];
        $mockUuid = 'uuid';

        $mockResponseData[$mockUuid] = [
            [
                "externalId" => "question-one",
                "question" => "Who provides your mortgage?",
                "prompts" => [
                    0 => "Nationwide",
                    1 => "Halifax",
                    2 => "Lloyds",
                    3 => "HSBC",
                ],
                "answered" => true,
            ],
            [
                "externalId" => "question-two",
                "question" => "Who provides your personal mobile contract?",
                "prompts" => [
                    0 => "EE",
                    1 => "Vodafone",
                    2 => "BT",
                    3 => "iMobile",
                ],
                "answered" => false,
            ],
            [
                "externalId" => "question-three",
                "question" => "What are the first two letters of the last name of another
                person on the electoral register at your address?",
                "prompts" => [
                    0 => "Ka",
                    1 => "Ch",
                    2 => "Jo",
                    3 => "None of the above",
                ],
                "answered" => false,
            ],
            [
                "externalId" => "question-four",
                "question" => "Who provides your current account?",
                "prompts" => [
                    0 => "Santander",
                    1 => "HSBC",
                    2 => "Halifax",
                    3 => "Nationwide",
                ],
                "answered" => false,
            ],
        ];

        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "1943-05-01",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "donor",
            "LPA" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA",
            ],
            "personType" => $personType,
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($mockUuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getIdCheckQuestions')
            ->willReturn($mockResponseData[$mockUuid]);

        $this->dispatch('/' . $mockUuid . '/id-verify-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class); // as specified in router's controller name alias
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('root/id_verify_questions');

        $this->assertQueryContentContains('h1', 'Who provides your personal mobile contract?');
        $this->assertQuery('input[type="radio"][name="question-two"]');
        $this->assertQuery('input[type="hidden"][name="question-three"]');
        $this->assertQuery('input[type="hidden"][name="question-four"]');
        $this->assertQueryCount('input[type="hidden"]', 2);
    }

    public static function personTypeDataProvider(): array
    {
        return [
            [
                'personType' => 'certificateProvider',
            ],
            [
                'personType' => 'donor',
            ],
        ];
    }

    /**
     * @dataProvider kbvOutcomeProvider
     */
    public function testKbvQuestionsResponses(string $personType, array $outcome, string $expectedRedirect): void
    {
        $uuid = '1b6b45ca-7f20-4110-afd4-1d6794423d3c';

        $this
            ->opgApiServiceMock
            ->expects($this->once())
            ->method('getDetailsData')
            ->with($uuid)
            ->willReturn([
                'personType' => $personType,
            ]);

        $this
            ->opgApiServiceMock
            ->expects($this->once())
            ->method('getIdCheckQuestions')
            ->with($uuid)
            ->willReturn([
                [
                    'externalId' => 'Q1',
                    'question' => 'Question?',
                    'prompts' => ['Whittaker', 'Broadway'],
                    'answered' => false,
                ],
            ]);

        $this
            ->opgApiServiceMock
            ->expects($this->once())
            ->method('checkIdCheckAnswers')
            ->with($uuid, [
                'answers' => [
                    'Q1' => 'Whittaker',
                    'Q2' => '27',
                ],
            ])
            ->willReturn($outcome);

        $this->dispatch('/' . $uuid . '/id-verify-questions', 'POST', [
            'Q1' => 'Whittaker',
            'Q2' => '27',
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo(sprintf($expectedRedirect, $uuid));
    }

    public static function kbvOutcomeProvider(): array
    {
        return [
            [
                'personType' => 'donor',
                'outcome' => ['complete' => false, 'passed' => false],
                'expectedRedirect' => '/%s/id-verify-questions',
            ],
            [
                'personType' => 'donor',
                'outcome' => ['complete' => true, 'passed' => false],
                'expectedRedirect' => '/%s/identity-check-failed',
            ],
            [
                'personType' => 'donor',
                'outcome' => ['complete' => true, 'passed' => true],
                'expectedRedirect' => '/%s/identity-check-passed',
            ],
            [
                'personType' => 'certificateProvider',
                'outcome' => ['complete' => false, 'passed' => false],
                'expectedRedirect' => '/%s/id-verify-questions',
            ],
            [
                'personType' => 'certificateProvider',
                'outcome' => ['complete' => true, 'passed' => false],
                'expectedRedirect' => '/%s/cp/identity-check-failed',
            ],
            [
                'personType' => 'certificateProvider',
                'outcome' => ['complete' => true, 'passed' => true],
                'expectedRedirect' => '/%s/cp/identity-check-passed',
            ],
        ];
    }
}

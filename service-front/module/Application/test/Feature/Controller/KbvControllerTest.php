<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\PersonType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class KbvControllerTest extends BaseControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;

    public function setUp(): void
    {
        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
    }

    #[DataProvider('personTypeDataProvider')]
    public function testKbvQuestionsFormRenders(PersonType $personType): void
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
        $this->assertMatchedRouteName('id_verify_questions');

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
                'personType' => PersonType::CertificateProvider,
            ],
            [
                'personType' => PersonType::Donor,
            ],
        ];
    }

    #[DataProvider('kbvOutcomeProvider')]
    public function testKbvQuestionsResponses(PersonType $personType, array $outcome, string $expectedRedirect): void
    {
        $uuid = '1b6b45ca-7f20-4110-afd4-1d6794423d3c';

        $this
            ->opgApiServiceMock
            ->expects($this->once())
            ->method('getDetailsData')
            ->with($uuid)
            ->willReturn([
                'personType' => $personType,
                'caseProgress' => ['fraudScore' => ['decision' => 'ACCEPT']],
                'lpas' => ['lpa1'],
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
                'personType' => PersonType::Donor,
                'outcome' => ['complete' => false, 'passed' => false],
                'expectedRedirect' => '/%s/id-verify-questions',
            ],
            [
                'personType' => PersonType::Donor,
                'outcome' => ['complete' => true, 'passed' => false],
                'expectedRedirect' => '/%s/identity-check-failed',
            ],
            [
                'personType' => PersonType::Donor,
                'outcome' => ['complete' => true, 'passed' => true],
                'expectedRedirect' => '/%s/identity-check-passed',
            ],
            [
                'personType' => PersonType::CertificateProvider,
                'outcome' => ['complete' => false, 'passed' => false],
                'expectedRedirect' => '/%s/id-verify-questions',
            ],
            [
                'personType' => PersonType::CertificateProvider,
                'outcome' => ['complete' => true, 'passed' => false],
                'expectedRedirect' => '/%s/identity-check-failed',
            ],
            [
                'personType' => PersonType::CertificateProvider,
                'outcome' => ['complete' => true, 'passed' => true],
                'expectedRedirect' => '/%s/cp/identity-check-passed',
            ],
            [
                'personType' => PersonType::Voucher,
                'outcome' => ['complete' => true, 'passed' => true],
                'expectedRedirect' => '/%s/vouching/identity-check-passed',
            ],
        ];
    }

    public function testIdentityCheckFailedPage(): void
    {
        $uuid = '1b6b45ca-7f20-4110-afd4-1d6794423d3c';

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($uuid)
            ->willReturn([
                'id' => $uuid,
                'personType' => PersonType::Donor,
            ]);

        $this->dispatch("/$uuid/identity-check-failed", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertMatchedRouteName('identity_check_failed');
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\ConfigBuilder;
use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\Soap\IIQClient;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IIQServiceTest extends TestCase
{
    private LoggerInterface|MockObject $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
    }
    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testStartAuthenticationAttempt(): void
    {
        $questions = [
            ['id' => 1],
            ['id' => 2],
        ];

        $client = $this->createMock(IIQClient::class);
        $config = $this->createMock(ConfigBuilder::class);

        $caseData = CaseData::fromArray([
            'id' => '68f0bee7-5b05-41da-95c4-2f1d5952184d',
            'firstName' => 'Albert',
            'lastName' => 'Williams',
            'address' => [
                'line1' => '123 long street',
            ],
        ]);

        $client->expects($this->once())
            ->method('__call')
            ->with(
                'SAA',
                $this->callback(fn ($args) => $args[0]['sAARequest']['Applicant']['Name']['Forename'] === 'Albert'),
            )
            ->willReturn((object)[
                'SAAResult' => (object)[
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                ],
            ]);

        $sut = new IIQService($client, $config, $this->logger);

        $this->assertEquals($questions, $sut->startAuthenticationAttempt($caseData));
    }
}

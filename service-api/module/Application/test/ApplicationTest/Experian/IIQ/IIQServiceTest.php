<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\Soap\IIQClient;
use PHPUnit\Framework\TestCase;

class IIQServiceTest extends TestCase
{
    public function testStartAuthenticationAttempt(): void
    {
        $questions = [
            ['id' => 1],
            ['id' => 2],
        ];

        $client = $this->createMock(IIQClient::class);

        $client->expects($this->once())
            ->method('__call')
            ->with('SAA', $this->callback(fn ($args) => $args[0]['sAARequest']['Applicant']['Name']['Forename'] === 'Albert'))
            ->willReturn((object)[
                'SAAResult' => (object)[
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                ],
            ]);

        $sut = new IIQService($client);

        $this->assertEquals($questions, $sut->startAuthenticationAttempt());
    }
}

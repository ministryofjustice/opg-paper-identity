<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Fixtures;

use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataQueryHandlerTest extends TestCase
{
    private DynamoDbClient|MockObject $dynamoDbClientMock;
    private DataQueryHandler $sut;

    public function setUp(): void
    {
        parent::setUp();
        // Mocking the DynamoDB client and logger
        $this->dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        // Create an instance of SUT with mocked dependencies
        $this->sut = new DataQueryHandler($this->dynamoDbClientMock, 'cases');
    }

    /**
     * @psalm-suppress UndefinedMagicMethod
     */
    public function testGetCaseByYotiSessionIdCallsIndex(): void
    {
        // Stub the query method of DynamoDB client
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'query',
                $this->callback(function ($params) {
                    // Ensure correct parameters passed to query
                    $input = $params[0];
                    $this->assertEquals('cases', $input['TableName']);
                    $this->assertEquals('yotiSessionId-index', $input['IndexName']);
                    return true;
                })
            )
            ->willReturn(new Result(
                ['Items' => [['yotiSessionId' => ['S' => 'a9bc8ab8-389c-4367-8a9b-762ab3050491']]]]
            ));

        $this->sut->queryByYotiSessionId('a9bc8ab8-389c-4367-8a9b-762ab3050491');
    }

    /**
     * @psalm-suppress UndefinedMagicMethod
     */
    public function testGetCaseByID(): void
    {
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'query',
                $this->callback(function ($params) {
                    // Ensure correct parameters passed to query
                    $input = $params[0];
                    $this->assertEquals('cases', $input['TableName']);
                    $this->assertArrayNotHasKey('IndexName', $input);
                    return true;
                })
            )
            ->willReturn(new Result(
                ['Items' => [['id' => ['S' => 'a9bc8ab8-389c-4367-8a9b-762ab3050492']]]]
            ));
        $case = $this->sut->getCaseByUUID('a9bc8ab8-389c-4367-8a9b-762ab3050492');

        $this->assertInstanceOf(CaseData::class, $case);
    }
}

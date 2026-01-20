<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Services;

use Application\Auth\JwtGenerator;
use Application\Enums\SiriusDocument;
use Application\Services\SiriusApiService;
use GuzzleHttp\Client;
use Laminas\Diactoros\ServerRequest;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerConfigInterface;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Lpa from SiriusApiService
 */
class SiriusApiServicePactTest extends TestCase
{
    private MockServerConfigInterface $pactConfig;
    private InteractionBuilder $builder;
    private LoggerInterface|MockObject $loggerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->pactConfig = new MockServerEnvConfig();

        $this->builder = new InteractionBuilder($this->pactConfig);
    }

    private function buildService(): SiriusApiService
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $client = new Client(['base_uri' => $this->pactConfig->getBaseUri()]);

        return new SiriusApiService($client, $this->loggerMock, $jwtGeneratorMock);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->builder->verify();
    }

    public function testSearchAddressesByPostcode(): void
    {
        $request = new ConsumerRequest();

        $request
            ->setMethod('GET')
            ->setPath('/api/v1/postcode-lookup')
            ->setQuery(['postcode' => 'B1 1TT']);

        $matcher = new Matcher();
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody($matcher->eachLike([
                'addressLine1' => $matcher->like('18 Leith Road'),
                'addressLine2' => $matcher->like('West Verpar'),
                'addressLine3' => $matcher->like('Smithhay'),
                'town' => $matcher->like('Forston'),
                'postcode' => $matcher->regex('FR6 2FJ', '^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$'),
            ]));

        $this->builder
            ->uponReceiving('A search for addresses by postcode')
            ->with($request)
            ->willRespondWith($response);

        $addresses = $this->buildService()->searchAddressesByPostcode('B1 1TT', new ServerRequest());

        $this->assertEquals('18 Leith Road', $addresses[0]['addressLine1']);
        $this->assertEquals('West Verpar', $addresses[0]['addressLine2']);
        $this->assertEquals('Smithhay', $addresses[0]['addressLine3']);
        $this->assertEquals('Forston', $addresses[0]['town']);
        $this->assertEquals('FR6 2FJ', $addresses[0]['postcode']);
    }

    public function testGetLpaByUid(): void
    {
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath('/api/v1/digital-lpas/M-1234-9876-4567');

        $matcher = new Matcher();
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                'opg.poas.sirius' => [
                    'donor' => [
                        'firstname' => $matcher->like('Erma'),
                        'surname' => $matcher->like('Muresan'),
                        'dob' => $matcher->regex('19/10/1930', '^\d{1,2}\/\d{1,2}\/\d{4}$'),
                        'addressLine1' => $matcher->like('18 Leith Road'),
                        'addressLine2' => $matcher->like('West Verpar'),
                        'addressLine3' => $matcher->like('Smithhay'),
                        'town' => $matcher->like('Forston'),
                        'postcode' => $matcher->like('FR6 2FJ'),
                        'country' => $matcher->like('GB'),
                    ],
                ],
                'opg.poas.lpastore' => [
                    'donor' => [
                        'firstNames' => $matcher->like('Mikel'),
                        'lastName' => $matcher->like('Lancz'),
                        'dateOfBirth' => $matcher->regex('1951-10-05', '^\d{4}-\d{1,2}-\d{1,2}$'),
                        'address' => [
                            'line1' => $matcher->like('Flat 19'),
                            'country' => $matcher->like('GB'),
                        ],
                    ],
                    'certificateProvider' => [
                        'firstNames' => $matcher->like('Dorian'),
                        'lastName' => $matcher->like('Rehkop'),
                        'address' => [
                            'line1' => $matcher->like('104, Alte Lindenstraße'),
                            'country' => $matcher->like('DE'),
                        ],
                    ],
                ],
            ]);

        $this->builder
            ->given('A digital LPA exists')
            ->uponReceiving('A request for an LPA')
            ->with($request)
            ->willRespondWith($response);

        $lpa = $this->buildService()->getLpaByUid('M-1234-9876-4567', new ServerRequest());

        $this->assertNotNull($lpa);

        $this->assertEquals('18 Leith Road', $lpa['opg.poas.sirius']['donor']['addressLine1']);

        $this->assertEquals('Erma', $lpa['opg.poas.sirius']['donor']['firstname']);
        $this->assertEquals('Muresan', $lpa['opg.poas.sirius']['donor']['surname']);
        $this->assertEquals('19/10/1930', $lpa['opg.poas.sirius']['donor']['dob']);
        $this->assertEquals('18 Leith Road', $lpa['opg.poas.sirius']['donor']['addressLine1']);
        $this->assertEquals('West Verpar', $lpa['opg.poas.sirius']['donor']['addressLine2'] ?? '');
        $this->assertEquals('Smithhay', $lpa['opg.poas.sirius']['donor']['addressLine3'] ?? '');
        $this->assertEquals('Forston', $lpa['opg.poas.sirius']['donor']['town'] ?? '');
        $this->assertEquals('FR6 2FJ', $lpa['opg.poas.sirius']['donor']['postcode'] ?? '');
        $this->assertEquals('GB', $lpa['opg.poas.sirius']['donor']['country']);

        $this->assertEquals('Mikel', $lpa['opg.poas.lpastore']['donor']['firstNames'] ?? '');
        $this->assertEquals('Lancz', $lpa['opg.poas.lpastore']['donor']['lastName'] ?? '');
        $this->assertEquals('1951-10-05', $lpa['opg.poas.lpastore']['donor']['dateOfBirth'] ?? '');

        $donorAddress = $lpa['opg.poas.lpastore']['donor']['address'] ?? [];
        $this->assertEquals('Flat 19', $donorAddress['line1'] ?? '');
        $this->assertEquals('GB', $donorAddress['country'] ?? '');

        $this->assertEquals('Dorian', $lpa['opg.poas.lpastore']['certificateProvider']['firstNames'] ?? '');
        $this->assertEquals('Rehkop', $lpa['opg.poas.lpastore']['certificateProvider']['lastName'] ?? '');

        $cpAddress = $lpa['opg.poas.lpastore']['certificateProvider']['address'] ?? [];
        $this->assertEquals('104, Alte Lindenstraße', $cpAddress['line1'] ?? '');
        $this->assertEquals('DE', $cpAddress['country'] ?? '');
    }

    /**
     * Returns an example of a tiny PDF with visible content
     */
    private function getMinimalPdf(): string
    {
        return "%PDF-1.2 \n
9 0 obj\n<<\n>>\nstream\nBT/ 32 Tf(  YOUR TEXT HERE   )' ET\nendstream\nendobj\n
4 0 obj\n<<\n/Type /Page\n/Parent 5 0 R\n/Contents 9 0 R\n>>\nendobj\n
5 0 obj\n<<\n/Kids [4 0 R ]\n/Count 1\n/Type /Pages\n/MediaBox [ 0 0 250 50 ]\n>>\nendobj\n
3 0 obj\n<<\n/Pages 5 0 R\n/Type /Catalog\n>>\nendobj\n
trailer\n<<\n/Root 3 0 R\n>>\n
%%EOF";
    }

    public function testsendDocument(): void
    {
        $details = [];
        $details["firstName"] = "Joe";
        $details["lastName"] = "Blogs";
        $details["address"]["line1"] = '123 Ferndale Road';
        $details["address"]["line2"] = 'Lambeth';
        $details["address"]["line3"] = 'Line 3';
        $details["address"]["town"] = 'London';
        $details["address"]["country"] = 'England';
        $details["address"]["postcode"] = 'SW4 7SS';
        $details["lpas"][0] = "M-1234-9876-4567";
        $details["vouchingFor"] = [
            "firstName" => 'Jane',
            "lastName" => 'Doe',
        ];

        $suffix = base64_encode($this->getMinimalPdf());
        $address = [
            '123 Ferndale Road',
            'Lambeth',
            'Line 3',
            'London',
            'England',
            'SW4 7SS',
        ];
        $body = [
            "type" => "Save",
            "systemType" => SiriusDocument::PostOfficeDocCheckVoucher,
            "content" => "",
            "pdfSuffix" => $suffix,
            "correspondentName" => "Joe Blogs",
            "correspondentAddress" => $address,
            "donorFirstNames" => "Jane",
            "donorLastName" => "Doe",
        ];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/v1/lpas/789/documents')
            ->setBody($body);

        $response = new ProviderResponse();
        $response
            ->setStatus(201);

        $this->builder
            ->given('A digital LPA exists')
            ->uponReceiving('A request to /api/v1/lpas/789/documents')
            ->with($request)
            ->willRespondWith($response);

        $client = new Client(['base_uri' => $this->pactConfig->getBaseUri()]);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $service = new class ($client, $loggerMock, $jwtGeneratorMock) extends SiriusApiService {
            /**
             * @return Lpa
             */
            public function getLpaByUid(string $uid, RequestInterface $request): array
            {
                return ['opg.poas.sirius' => [
                    'uId' => 'M-0000-0000-0000',
                    'id' => 789,
                    'caseSubtype' => 'property-and-affairs',
                    'donor' => [
                        'firstname' => 'Susan',
                        'surname' => 'Muller',
                        'dob' => '1980-06-30',
                        'addressLine1' => 'Vandammeplein 8',
                        'town' => 'Hernezele',
                        'country' => 'BE',
                    ],
                ], 'opg.poas.lpastore' => null];
            }
        };

        $result = $service->sendDocument(
            $details,
            SiriusDocument::PostOfficeDocCheckVoucher,
            new ServerRequest(),
            $suffix
        );

        $this->assertEquals(201, $result['status']);
    }
}

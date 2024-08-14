<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use GuzzleHttp\Client;
use Application\Services\SiriusApiService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use PHPUnit\Framework\TestCase;
use Throwable;

class SiriusApiServicePactTest extends TestCase
{
    private InteractionBuilder $builder;
    private SiriusApiService $sut;

    public function setUp(): void
    {
        $config = new MockServerEnvConfig();

        $client = new Client(['base_uri' => $config->getBaseUri()]);
        $this->sut = new SiriusApiService($client);

        $this->builder = new InteractionBuilder($config);
    }

    public function tearDown(): void
    {
        try {
            $this->assertTrue($this->builder->verify());
        } catch (Throwable) {
        }

        try {
            $this->builder->finalize();
        } catch (Throwable) {
        }

        parent::tearDown();
    }

    public function testSearchAddressesByPostcode(): void
    {
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath('/api/v1/postcode-lookup')
            ->setQuery('postcode=B1%201TT');

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

        $addresses = $this->sut->searchAddressesByPostcode('B1 1TT', new Request());

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
                        ]
                    ],
                    'certificateProvider' => [
                        'firstNames' => $matcher->like('Dorian'),
                        'lastName' => $matcher->like('Rehkop'),
                        'address' => [
                            'line1' => $matcher->like('104, Alte Lindenstraße'),
                            'country' => $matcher->like('DE'),
                        ]
                    ]
                ]
            ]);


        $this->builder
            ->given('A digital LPA exists')
            ->uponReceiving('A request for an LPA')
            ->with($request)
            ->willRespondWith($response);

        $lpa = $this->sut->getLpaByUid('M-1234-9876-4567', new Request());

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

    public function testAbandonCase(): void
    {
        $request = new ConsumerRequest();
        $body = [
            "reference" => "49895f88-501b-4491-8381-e8aeeaef177d",
            "actorType" => "donor",
            "lpaIds" => [
                "M-0000-0000-0000"
            ],
            "time" => "2024-07-30T10:53:57+00:00",
            "outcome" => "exit"
        ];
        $request
            ->setMethod('POST')
            ->setPath('/api/v1/identity-check')
            ->setBody($body);

        $response = new ProviderResponse();
        $response
            ->setStatus(204);

        $this->builder
            ->uponReceiving('A notification that case was exited')
            ->with($request)
            ->willRespondWith($response);

        $response = $this->sut->abandonCase($body, new Request());

        $this->assertEquals(204, $response['status']);
        $this->assertEquals("", $response['error']);
    }

    public function testSendPostOfficePdf(): void
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
        $details["lpas"][0] = "789";

        $suffix = base64_encode('Test');
        $address = [
            '123 Ferndale Road',
            'Lambeth',
            'Line 3',
            'London',
            'England',
            'SW4 7SS'
        ];
        $body = [
            "type" => "Save",
            "systemType" => "DLP-ID-PO-D",
            "content" => "",
            "suffix" => $suffix,
            "correspondentName" => "Joe Blogs",
            "correspondentAddress" => $address
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
            ->uponReceiving('A post request /api/v1/lpas/789/documents')
            ->with($request)
            ->willRespondWith($response);

        $result = $this->sut->sendPostOfficePDf($suffix, $details);
        $this->assertEquals(201, $result['status']);
    }
}

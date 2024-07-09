<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use GuzzleHttp\Client;
use Application\Services\SiriusApiService;
use Laminas\Http\Request;
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
                        'dateOfBirth' => $matcher->regex('05/10/1951', '^\d{1,2}\/\d{1,2}\/\d{4}$'),
                        'address' => [
                            'line1' => $matcher->like('Flat 19'),
                            'line2' => $matcher->like('Newtown House'),
                            'line3' => $matcher->like('Notting Hill'),
                            'town' => $matcher->like('London'),
                            'postcode' => $matcher->like('EV1 9AE'),
                            'country' => $matcher->like('GB'),
                        ]
                    ],
                    'certificateProvider' => [
                        'firstNames' => $matcher->like('Dorian'),
                        'lastName' => $matcher->like('Rehkop'),
                        'address' => [
                            'line1' => $matcher->like('104, Alte Lindenstraße'),
                            'line2' => $matcher->like('Willmersdorf'),
                            'line3' => $matcher->like('Cottbus-Chóśebuz'),
                            'town' => $matcher->like('Brandenburg'),
                            'postcode' => $matcher->like('03053'),
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
        $this->assertEquals('05/10/1951', $lpa['opg.poas.lpastore']['donor']['dateOfBirth'] ?? '');

        $donorAddress = $lpa['opg.poas.lpastore']['donor']['address'] ?? [];
        $this->assertEquals('Flat 19', $donorAddress['line1'] ?? '');
        $this->assertEquals('Newtown House', $donorAddress['line2'] ?? '');
        $this->assertEquals('Notting Hill', $donorAddress['line3'] ?? '');
        $this->assertEquals('London', $donorAddress['town'] ?? '');
        $this->assertEquals('EV1 9AE', $donorAddress['postcode'] ?? '');
        $this->assertEquals('GB', $donorAddress['country'] ?? '');

        $this->assertEquals('Dorian', $lpa['opg.poas.lpastore']['certificateProvider']['firstNames'] ?? '');
        $this->assertEquals('Rehkop', $lpa['opg.poas.lpastore']['certificateProvider']['lastName'] ?? '');

        $cpAddress = $lpa['opg.poas.lpastore']['certificateProvider']['address'] ?? [];
        $this->assertEquals('104, Alte Lindenstraße', $cpAddress['line1'] ?? '');
        $this->assertEquals('Willmersdorf', $cpAddress['line2'] ?? '');
        $this->assertEquals('Cottbus-Chóśebuz', $cpAddress['line3'] ?? '');
        $this->assertEquals('Brandenburg', $cpAddress['town'] ?? '');
        $this->assertEquals('03053', $cpAddress['postcode'] ?? '');
        $this->assertEquals('DE', $cpAddress['country'] ?? '');
    }
}

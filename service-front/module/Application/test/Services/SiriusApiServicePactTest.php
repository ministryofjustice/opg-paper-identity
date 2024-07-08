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
}

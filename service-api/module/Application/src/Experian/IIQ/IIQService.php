<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Soap\IIQClient;
use Psr\Log\LoggerInterface;
use SoapFault;

class IIQService
{
    private bool $isAuthenticated = false;

    public function __construct(
        private readonly AuthManager $authManager,
        private readonly IIQClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withAuthentication(callable $callback): mixed
    {
        if (! $this->isAuthenticated) {
            $this->client->__setSoapHeaders([
                $this->authManager->buildSecurityHeader(),
            ]);

            $this->isAuthenticated = true;
        }

        try {
            return $callback();
        } catch (SoapFault $e) {
            if ($e->getMessage() === 'Unauthorized') {
                $this->logger->info('IIQ API replied unauthorised, retrying with new token');

                $this->client->__setSoapHeaders([
                    $this->authManager->buildSecurityHeader(true),
                ]);

                return $callback();
            } else {
                throw $e;
            }
        }
    }

    public function startAuthenticationAttempt(): array
    {
        return $this->withAuthentication(function () {
            $request = $this->client->SAA([
                'sAARequest' => [
                    'Applicant' => [
                        'ApplicantIdentifier' => '1',
                        'Name' => [
                            'Title' => 'Mr',
                            'Forename' => 'Albert',
                            'Surname' => 'Arkil',
                        ],
                        'DateOfBirth' => [
                            'CCYY' => '1951',
                            'MM' => '02',
                            'DD' => '18',

                        ],
                    ],
                    'ApplicationData' => [
                        'SearchConsent' => 'Y',
                    ],
                    'Control' => [
                        'TestDatabase' => 'A',
                    ],
                    'LocationDetails' => [
                        'LocationIdentifier' => '1',
                        'UKLocation' => [
                            'HouseNumber' => '3',
                            'Street' => 'STOCKS HILL',
                            'District' => 'HIGH HARRINGTON',
                            'PostTown' => 'WORKINGTON',
                            'Postcode' => 'CA14 5PH',
                        ],
                    ],
                ],
            ]);

            return (array)$request->SAAResult->Questions->Question;
        });
    }
}

<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Soap\IIQClient;

class IIQService
{
    private bool $isAuthenticated = false;

    public function __construct(
        private readonly AuthManager $authManager,
        private readonly IIQClient $client,
    ) {
    }

    public function authenticate(): void
    {
        if (!$this->isAuthenticated) {
            $this->client->__setSoapHeaders([
                $this->authManager->buildSecurityHeader(),
            ]);

            $this->isAuthenticated = true;
        }
    }

    public function startAuthenticationAttempt(): array
    {
        $this->authenticate();

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
    }
}

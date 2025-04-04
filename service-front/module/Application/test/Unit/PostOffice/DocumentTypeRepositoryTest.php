<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\PostOffice;

use Application\Enums\DocumentType;
use Application\PostOffice\Country;
use Application\PostOffice\DocumentTypeRepository;
use PHPUnit\Framework\TestCase;

class DocumentTypeRepositoryTest extends TestCase
{
    public function testGetByCountry(): void
    {
        $documentTypeRepository = new DocumentTypeRepository([
            [
                'code' => 'AUS',
                'supported_documents' => [
                    ['type' => 'PASSPORT',],
                    ['type' => 'NATIONAL_ID'],
                ],
            ],
            [
                'code' => 'NGA',
                'supported_documents' => [
                    ['type' => 'DRIVING_LICENCE'],
                ],
            ],
            [
                'code' => 'AUT',
                'supported_documents' => [
                    ['type' => 'PASSPORT'],
                    ['type' => 'DRIVING_LICENCE'],
                    ['type' => 'NATIONAL_ID'],
                    ['type' => 'RESIDENCE_PERMIT'],
                ],
            ],
            [
                'code' => 'FRA',
                'supported_documents' => [
                    ['type' => 'NATIONAL_ID'],
                    ['type' => 'TRAVEL_DOCUMENT'],
                ],
            ],
        ]);

        $this->assertEquals([DocumentType::Passport], $documentTypeRepository->getByCountry(Country::AUS));
        $this->assertEquals([], $documentTypeRepository->getByCountry(Country::NGA));
        $this->assertEquals([], $documentTypeRepository->getByCountry(Country::VEN));

        $this->assertEquals(
            [DocumentType::Passport, DocumentType::DrivingLicence, DocumentType::NationalId],
            $documentTypeRepository->getByCountry(Country::AUT)
        );
        $this->assertEquals([DocumentType::NationalId], $documentTypeRepository->getByCountry(Country::FRA));
        $this->assertEquals([], $documentTypeRepository->getByCountry(Country::POL));
    }
}

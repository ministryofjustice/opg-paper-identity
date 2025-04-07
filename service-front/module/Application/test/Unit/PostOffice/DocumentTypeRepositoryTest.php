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
                    ['type' => DocumentType::Passport->value,],
                    ['type' => DocumentType::NationalId->value],
                ],
            ],
            [
                'code' => 'NGA',
                'supported_documents' => [
                    ['type' => DocumentType::DrivingLicence->value],
                ],
            ],
            [
                'code' => 'AUT',
                'supported_documents' => [
                    ['type' => DocumentType::Passport->value],
                    ['type' => DocumentType::DrivingLicence->value],
                    ['type' => DocumentType::NationalId->value],
                    ['type' => DocumentType::ResidencePermit->value],
                ],
            ],
            [
                'code' => 'FRA',
                'supported_documents' => [
                    ['type' => DocumentType::NationalId->value],
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

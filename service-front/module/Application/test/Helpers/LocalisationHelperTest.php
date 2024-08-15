<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Exceptions\LocalisationException;
use Application\Helpers\LocalisationHelper;
use Application\Helpers\LpaFormHelper;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;
use Application\Forms\LpaReferenceNumber;

class LocalisationHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider documentData
     */
    public function testProcessDocumentBody(
        array $documentData,
        array $expected
    ): void {
        $localisationHelper = new LocalisationHelper($this->configData());

        $actual = $localisationHelper->processDocumentBody($documentData);

        $this->assertEquals($expected, $actual);
    }


    public static function documentData(): array
    {
        return [
            [
                [
                    "code" => "AUT",
                    "supported_documents" => [
                        [
                            "type" => "DRIVING_LICENCE",
                            "is_strictly_latin" => true
                        ],
                        [
                            "type" => "NATIONAL_ID",
                            "is_strictly_latin" => true,
                            "requirements" => [
                                "date_from" => "2002-01-01"
                            ]
                        ],
                        [
                            "type" => "PASSPORT",
                            "is_strictly_latin" => true
                        ],
                        [
                            "type" => "RESIDENCE_PERMIT",
                            "is_strictly_latin" => true
                        ],
                        [
                            "type" => "TRAVEL_DOCUMENT",
                            "is_strictly_latin" => true
                        ]
                    ]
                ],
                [
                    "code" => "AUT",
                    "supported_documents" => [
                        [
                            "type" => "DRIVING_LICENCE",
                            "is_strictly_latin" => true,
                            "display_text" => "Driving licence"
                        ],
                        [
                            "type" => "NATIONAL_ID",
                            "is_strictly_latin" => true,
                            "requirements" => [
                                "date_from" => "2002-01-01"
                            ],
                            "display_text" => "National ID"
                        ],
                        [
                            "type" => "PASSPORT",
                            "is_strictly_latin" => true,
                            "display_text" => "Passport"
                        ],
                        [
                            "type" => "RESIDENCE_PERMIT",
                            "is_strictly_latin" => true,
                            "display_text" => "Residence permit"
                        ],
                        [
                            "type" => "TRAVEL_DOCUMENT",
                            "is_strictly_latin" => true,
                            "display_text" => "Travel document"
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider wordData
     */
    public function testDisplayText(string $word, string $expected): void
    {
        $localisationHelper = new LocalisationHelper($this->configData());
        $actual = $localisationHelper->addDisplayText($word);

        $this->assertEquals($expected, $actual);
    }

    public static function wordData(): array
    {
        return [
            [
                'DRIVING_LICENCE',
                'Driving licence',
            ],
            [
                'NATIONAL_ID',
                'National ID'
            ],
            [
                'PASSPORT',
                'Passport'
            ],
            [
                'RESIDENCE_PERMIT',
                'Residence permit'
            ],
            [
                'TRAVEL_DOCUMENT',
                'Travel document'
            ]
        ];
    }

    /**
     * @dataProvider getInternationalSupportedDocumentsData
     */
    public function testGetInternationalSupportedDocuments(
        array $config,
        string $word,
        array $expected,
        bool $exception = false
    ): void {
        if ($exception) {
            $this->expectException(LocalisationException::class);
        }
        $localisationHelper = new LocalisationHelper($config);
        $actual = $localisationHelper->getInternationalSupportedDocuments($word);

        $this->assertEquals($expected, $actual['supported_documents']);
    }

    public function configData(): array
    {
        return [
            'opg_settings' => [
                'identity_documents' => [
                    'PASSPORT' => "Passport",
                    'DRIVING_LICENCE' => 'Driving licence',
                    'NATIONAL_ID' => 'National ID',
                    'RESIDENCE_PERMIT' => 'Residence permit',
                    'TRAVEL_DOCUMENT' => 'Travel document',
                    'NATIONAL_INSURANCE_NUMBER' => 'National Insurance number'
                ],
                'identity_routes' => [
                    'TELEPHONE' => 'Telephone',
                    'POST_OFFICE' => 'Post office',
                ],
                'identity_methods' => [
                    'nin' => 'National Insurance number',
                    'pn' => 'UK Passport (current or expired in the last 5 years)',
                    'dln' => 'UK photocard driving licence (must be current) ',
                ],
                'post_office_identity_methods' => [
                    'po_ukp' => 'UK passport (up to 18 months expired)',
                    'po_eup' => 'EU passport (must be current)',
                    'po_inp' => 'International passport (must be current)',
                    'po_ukd' => 'UK Driving licence (must be current)',
                    'po_eud' => 'EU Driving licence (must be current)',
                    'po_ind' => 'International driving licence (must be current)',
                    'po_n' => 'None of the above',
                ],
                'non_uk_identity_methods' => [
                    'xpn' => 'Passport',
                    'xdln' => 'Photocard driving licence',
                    'xid' => 'National identity card',
                ],
                'yoti_identity_methods' => [
                    'PASSPORT' => "Passport",
                    'DRIVING_LICENCE' => 'Driving licence',
                    'NATIONAL_ID' => 'National ID',
                    'RESIDENCE_PERMIT' => 'Residence permit',
                    'TRAVEL_DOCUMENT' => 'Travel document',
                ],
                'localisation' => [
                    "AUT" => [
                        "code" => "AUT",
                        "supported_documents" => [
                            [
                                "type" => "DRIVING_LICENCE",
                                "is_strictly_latin" => true
                            ],
                            [
                                "type" => "NATIONAL_ID",
                                "is_strictly_latin" => true,
                                "requirements" => [
                                    "date_from" => "2002-01-01"
                                ]
                            ],
                            [
                                "type" => "PASSPORT",
                                "is_strictly_latin" => true
                            ],
                            [
                                "type" => "RESIDENCE_PERMIT",
                                "is_strictly_latin" => true
                            ],
                            [
                                "type" => "TRAVEL_DOCUMENT",
                                "is_strictly_latin" => true
                            ]
                        ],
                        "name" => "Austria"
                    ],
                ],
            ]
        ];
    }

    public static function getInternationalSupportedDocumentsData(): array
    {
        $config = [
            'opg_settings' => [
                'identity_documents' => [
                    'PASSPORT' => "Passport",
                    'DRIVING_LICENCE' => 'Driving licence',
                    'NATIONAL_ID' => 'National ID',
                    'RESIDENCE_PERMIT' => 'Residence permit',
                    'TRAVEL_DOCUMENT' => 'Travel document',
                    'NATIONAL_INSURANCE_NUMBER' => 'National Insurance number'
                ],
                'identity_routes' => [
                    'TELEPHONE' => 'Telephone',
                    'POST_OFFICE' => 'Post office',
                ],
                'identity_methods' => [
                    'nin' => 'National Insurance number',
                    'pn' => 'UK Passport (current or expired in the last 5 years)',
                    'dln' => 'UK photocard driving licence (must be current) ',
                ],
                'post_office_identity_methods' => [
                    'po_ukp' => 'UK passport (up to 18 months expired)',
                    'po_eup' => 'EU passport (must be current)',
                    'po_inp' => 'International passport (must be current)',
                    'po_ukd' => 'UK Driving licence (must be current)',
                    'po_eud' => 'EU Driving licence (must be current)',
                    'po_ind' => 'International driving licence (must be current)',
                    'po_n' => 'None of the above',
                ],
                'non_uk_identity_methods' => [
                    'xpn' => 'Passport',
                    'xdln' => 'Photocard driving licence',
                    'xid' => 'National identity card',
                ],
                'yoti_identity_methods' => [
                    'PASSPORT' => "Passport",
                    'DRIVING_LICENCE' => 'Driving licence',
                    'NATIONAL_ID' => 'National ID',
                    'RESIDENCE_PERMIT' => 'Residence permit',
                    'TRAVEL_DOCUMENT' => 'Travel document',
                ],
                'localisation' => [
                    "AUT" => [
                        "code" => "AUT",
                        "supported_documents" => [
                            [
                                "type" => "DRIVING_LICENCE",
                                "is_strictly_latin" => true
                            ],
                            [
                                "type" => "NATIONAL_ID",
                                "is_strictly_latin" => true,
                                "requirements" => [
                                    "date_from" => "2002-01-01"
                                ]
                            ],
                            [
                                "type" => "PASSPORT",
                                "is_strictly_latin" => true
                            ],
                            [
                                "type" => "RESIDENCE_PERMIT",
                                "is_strictly_latin" => true
                            ],
                            [
                                "type" => "TRAVEL_DOCUMENT",
                                "is_strictly_latin" => true
                            ]
                        ],
                        "name" => "Austria"
                    ],
                ],
            ]
        ];

        return [
          [
              $config,
              "AUT",
              [
                  [
                      "type" => "DRIVING_LICENCE",
                      "is_strictly_latin" => true,
                      "display_text" => "Driving licence"
                  ],
                  [
                      "type" => "NATIONAL_ID",
                      "is_strictly_latin" => true,
                      "requirements" => [
                          "date_from" => "2002-01-01"
                      ],
                      "display_text" => "National ID"
                  ],
                  [
                      "type" => "PASSPORT",
                      "is_strictly_latin" => true,
                      "display_text" => "Passport"
                  ],
                  [
                      "type" => "RESIDENCE_PERMIT",
                      "is_strictly_latin" => true,
                      "display_text" => "Residence permit"
                  ],
                  [
                      "type" => "TRAVEL_DOCUMENT",
                      "is_strictly_latin" => true,
                      "display_text" => "Travel document"
                  ]
              ]
          ],
            [
                $config,
                "AU",
                [],
                true
            ]
        ];
    }
}

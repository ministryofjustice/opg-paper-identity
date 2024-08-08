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
        $localisationHelper = new LocalisationHelper([]);

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
        $localisationHelper = new LocalisationHelper([]);
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
     * @dataProvider configData
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

    public static function configData(): array
    {
        return [
          [
              [
                  'opg_settings' => [
                      'acceptable_nations_for_id_documents' => [
                          'AUT' => 'Austria',
                      ],
                      "supported_countries_documents" => [
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
                          ]
                      ]
                  ]
              ],
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
                [
                    'opg_settings' => [
                        'acceptable_nations_for_id_documents' => [
                            'AUT' => 'Austria',
                        ],
                        "supported_countries_documents" => [
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
                            ]
                        ]
                    ]
                ],
                "AU",
                [],
                true
            ]
        ];
    }
}

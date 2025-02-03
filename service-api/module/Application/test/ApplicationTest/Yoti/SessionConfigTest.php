<?php

declare(strict_types=1);

namespace ApplicationTest\Yoti;

use Application\Model\Entity\CaseData;
use Application\Model\Entity\IdMethodIncludingNation;
use Application\Yoti\SessionConfig;
use DateTime;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SessionConfigTest extends TestCase
{
    private CaseData $caseMock;
    private SessionConfig $sut;
    private string $uuid;
    public function setUp(): void
    {
        parent::setUp();

        $this->caseMock = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'personType' => 'donor',
            'claimedIdentity' => [
                'firstName' => 'Maria',
                'lastName' => 'Williams',
                'dob' => '1970-01-01',
                'address' => [
                    'line1' => '123 long street',
                    'line2' => 'Kings Cross',
                    'line3' => 'London',
                    'postcode' => 'NW1 1SP',
                    'country' => 'England'
                ]
            ],
            "idMethodIncludingNation" => [
                'id_method' => "PASSPORT",
                'id_country' => "GBR",
                'id_route' => "POST_OFFICE",
            ],
            'lpas' => []
        ]);
        $this->uuid = strval(Uuid::uuid4());
        // an instance of SUT
        $this->sut = new SessionConfig();
    }

    public function testSessionFormat(): void
    {
        $sessionConfig = $this->sut->build($this->caseMock, $this->uuid);

        $this->assertEquals($this->sessionConfigExpected(), $sessionConfig);
    }

    public function testSessionWithForeignId(): void
    {
        $idIncludingNation = IdMethodIncludingNation::fromArray([
            "id_country" => "ITA",
            "id_method" => "DRIVING_LICENCE",
            "id_route" => 'POST_OFFICE',
            "id_value" => 'AA112233C'
        ]);
        $this->caseMock->idMethodIncludingNation = $idIncludingNation;

        $expectedConfig = $this->sessionConfigExpected();
        $expectedConfig["required_documents"][0]["filter"]["documents"][0]["country_codes"][0] = "ITA";
        $expectedConfig["required_documents"][0]["filter"]["documents"][0]["document_types"][0] = "DRIVING_LICENCE";

        $sessionConfig = $this->sut->build($this->caseMock, $this->uuid);

        $this->assertEquals($expectedConfig, $sessionConfig);
    }


    public function sessionConfigExpected(): array
    {
        $currentDate = new DateTime();
        $deadlineSet = (string)getenv("YOTI_SESSION_DEADLINE") ? : '30';
        $modifierString = '+' . $deadlineSet . ' days';
        $currentDate->modify($modifierString);
        $currentDate->setTime(22, 0, 0);

        $sessionConfig = [];
        $sessionConfig["session_deadline"] = $currentDate->format(DateTime::ATOM);
        $sessionConfig["resources_ttl"] = intval($currentDate->format('U')) - time() + 86400;
        $sessionConfig["ibv_options"]["support"] = 'MANDATORY';
        $sessionConfig["user_tracking_id"] = $this->caseMock->id;
        $sessionConfig["notifications"] = [
            "endpoint" => getenv("YOTI_NOTIFICATION_URL"),
            "topics" => [
                "FIRST_BRANCH_VISIT",
                "THANK_YOU_EMAIL_REQUESTED",
                "INSTRUCTIONS_EMAIL_REQUESTED",
                "SESSION_COMPLETION"
            ],
            "auth_token" => $this->uuid,
            "auth_type" => 'BEARER',
        ];

        $sessionConfig["requested_checks"] = [
            [
                "type" => "IBV_VISUAL_REVIEW_CHECK",
                "config" => [
                    "manual_check" => "IBV"
                ]
            ],
            [
                "type" => "PROFILE_DOCUMENT_MATCH",
                "config" => [
                    "manual_check" => "IBV"
                ]
            ],
            [
                "type" => "DOCUMENT_SCHEME_VALIDITY_CHECK",
                "config" => [
                    "manual_check" => "IBV",
                    "scheme" => "UK_GDS"
                ]
            ],
            [
                "type" => "ID_DOCUMENT_AUTHENTICITY",
                "config" => [
                    "manual_check" => "ALWAYS"
                ]
            ],
            [
                "type" => "ID_DOCUMENT_FACE_MATCH",
                "config" => [
                    "manual_check" => "FALLBACK"
                ]
            ]
        ];
        $sessionConfig["requested_tasks"] = [
            [
                "type" => "ID_DOCUMENT_TEXT_DATA_EXTRACTION",
                "config" => [
                    "manual_check" => "FALLBACK"
                ]
            ]
        ];
        $sessionConfig["required_documents"] = [
            [
                "type" => "ID_DOCUMENT",
                "filter" => [
                    "type" => "DOCUMENT_RESTRICTIONS",
                    "inclusion" => "INCLUDE",
                    "documents" => [
                        [
                            "country_codes" => ["GBR"],
                            "document_types" => ["PASSPORT"]
                        ]
                    ]
                ]
            ]
        ];

        $sessionConfig["resources"] = [
            "applicant_profile" => [
                "given_names" => $this->caseMock->claimedIdentity?->firstName,
                "family_name" => $this->caseMock->claimedIdentity?->lastName,
                "date_of_birth" => $this->caseMock->claimedIdentity?->dob,
                "structured_postal_address" => [
                    "address_format" => "1",
                    "building_number" => "123",
                    "address_line1" => '123 long street',
                    "address_line2" => 'Kings Cross',
                    "town_city" => "London",
                    "country" => 'England',
                    "country_iso" => "GBR",
                    "postal_code" => 'NW1 1SP',
                ],
            ]
        ];

        return $sessionConfig;
    }
}

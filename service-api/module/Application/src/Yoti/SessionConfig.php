<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiException;
use DateTime;
use Ramsey\Uuid\Uuid;

class SessionConfig
{
    public function build(CaseData $case, string $uuid): array
    {
        $sessionConfig = [];
        $authToken = $uuid;

        $sessionConfig["session_deadline"] = $this->deadlineDate();
        $sessionConfig["resources_ttl"] = $this->getResourceTtl();
        $sessionConfig["ibv_options"]["support"] = 'MANDATORY';
        $sessionConfig["user_tracking_id"] = $case->id;
        $sessionConfig["notifications"] = [
            "endpoint" => getenv("YOTI_NOTIFICATION_URL"),
            "topics" => [
                "FIRST_BRANCH_VISIT",
                "THANK_YOU_EMAIL_REQUESTED",
                "INSTRUCTIONS_EMAIL_REQUESTED",
                "SESSION_COMPLETION"
            ],
            "auth_token" => $authToken,
            "auth_type" => 'BEARER'
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
                            "country_codes" => [$this->getIDCountry($case)],
                            "document_types" => [$this->getDocType($case)]
                        ]
                    ]
                ]
            ]
        ];

        if ($case->claimedIdentity) {
            $sessionConfig["resources"] = [
                "applicant_profile" => [
                    "given_names" => $case->claimedIdentity->firstName,
                    "family_name" => $case->claimedIdentity->lastName,
                    "date_of_birth" => $case->claimedIdentity->dob,
                    "structured_postal_address" => $this->addressFormatted($case->claimedIdentity->address),
                ]
            ];
        }

        return $sessionConfig;
    }

    public function getResourceTtl(): int
    {
        $deadlineTime = strtotime($this->deadlineDate());
        if ($deadlineTime === false) {
            throw new YotiException("Invalid deadline date");
        }

        $deadlineSeconds = $deadlineTime - time();

        return $deadlineSeconds + 86400;
    }

    public function deadlineDate(): string
    {
        $currentDate = new DateTime();

        // Add number of days for session dateline as loaded via env
        $deadlineSet = (string)getenv("YOTI_SESSION_DEADLINE") ? : '30';
        $modifierString = '+' . $deadlineSet . ' days';
        $currentDate->modify($modifierString);

        // Set the time to 22:00
        $currentDate->setTime(22, 0, 0);
        // Format the date to ISO 8601 string
        return $currentDate->format(DateTime::ATOM);
    }

    /**
     * @param CaseData $case
     * @return string
     */
    public static function getIDCountry(CaseData $case): string
    {
        $nonUKIDs = $case->idMethodIncludingNation;
        if (isset($nonUKIDs->id_country)) {
            return $nonUKIDs->id_country;
        }

        return 'GBR';
    }

    /**
     * @param CaseData $case
     * @return string
     */
    public static function getDocType(CaseData $case): string
    {
        $nonUKIDs = $case->idMethodIncludingNation;

        if (isset($nonUKIDs->id_method)) {
            return $nonUKIDs->id_method;
        }
        return "PASSPORT";
    }

    /**
     * @throws YotiException
     */
    public function addressFormatted(?array $address): array
    {
        $addressFormat = [];
        if (is_null($address)) {
            throw new YotiException("Address is not set");
        } elseif (! $address['line1'] || $address['line1'] == '') {
            throw new YotiException("Address line1 missing");
        }

        $addressFormat["address_format"] = "1";
        $addressFormat["building_number"] = substr($address['line1'], 0, 3);
        $addressFormat["address_line1"] = $address['line1'];
        $addressFormat["address_line2"] = $address['line2'];
        $addressFormat["town_city"] = $address['line3'] ?? $address['line2'];
        $addressFormat["country"] = $address['country'];
        $addressFormat["country_iso"] = "GBR";
        $addressFormat["postal_code"] = $address['postcode'];

        return $addressFormat;
    }
}

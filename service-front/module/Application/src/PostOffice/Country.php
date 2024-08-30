<?php

declare(strict_types=1);

namespace Application\PostOffice;

enum Country: string
{
    /**
     * @var Country[]
     */
    private const EU_EEA_COUNTRY_CODES = [
        // EU
        self::AUT,
        self::BEL,
        self::BGR,
        self::HRV,
        self::CYP,
        self::CZE,
        self::DNK,
        self::EST,
        self::FIN,
        self::FRA,
        self::DEU,
        self::GRC,
        self::HUN,
        self::IRL,
        self::ITA,
        self::LVA,
        self::LTU,
        self::LUX,
        self::MLT,
        self::NLD,
        self::POL,
        self::PRT,
        self::ROU,
        self::SVK,
        self::SVN,
        self::ESP,
        self::SWE,
        // EEA
        self::ISL,
        self::LIE,
        self::NOR,
    ];

    case AFG = 'AFG';
    case AGO = 'AGO';
    case AIA = 'AIA';
    case ALB = 'ALB';
    case AND = 'AND';
    case ARE = 'ARE';
    case ARG = 'ARG';
    case ARM = 'ARM';
    case ASM = 'ASM';
    case ATG = 'ATG';
    case AUS = 'AUS';
    case AUT = 'AUT';
    case AZE = 'AZE';
    case BDI = 'BDI';
    case BEL = 'BEL';
    case BEN = 'BEN';
    case BFA = 'BFA';
    case BGD = 'BGD';
    case BGR = 'BGR';
    case BHR = 'BHR';
    case BHS = 'BHS';
    case BIH = 'BIH';
    case BLR = 'BLR';
    case BLZ = 'BLZ';
    case BMU = 'BMU';
    case BOL = 'BOL';
    case BRA = 'BRA';
    case BRB = 'BRB';
    case BRN = 'BRN';
    case BTN = 'BTN';
    case BWA = 'BWA';
    case CAF = 'CAF';
    case CAN = 'CAN';
    case CHE = 'CHE';
    case CHL = 'CHL';
    case CHN = 'CHN';
    case CIV = 'CIV';
    case CMR = 'CMR';
    case COD = 'COD';
    case COG = 'COG';
    case COL = 'COL';
    case COM = 'COM';
    case CPV = 'CPV';
    case CRI = 'CRI';
    case CUB = 'CUB';
    case CYM = 'CYM';
    case CYP = 'CYP';
    case CZE = 'CZE';
    case DEU = 'DEU';
    case DJI = 'DJI';
    case DMA = 'DMA';
    case DNK = 'DNK';
    case DOM = 'DOM';
    case DZA = 'DZA';
    case ECU = 'ECU';
    case EGY = 'EGY';
    case ERI = 'ERI';
    case ESP = 'ESP';
    case EST = 'EST';
    case ETH = 'ETH';
    case FIN = 'FIN';
    case FJI = 'FJI';
    case FRA = 'FRA';
    case FRO = 'FRO';
    case FSM = 'FSM';
    case GAB = 'GAB';
    case GBR = 'GBR';
    case GEO = 'GEO';
    case GGY = 'GGY';
    case GHA = 'GHA';
    case GIB = 'GIB';
    case GIN = 'GIN';
    case GMB = 'GMB';
    case GNB = 'GNB';
    case GNQ = 'GNQ';
    case GRC = 'GRC';
    case GRD = 'GRD';
    case GRL = 'GRL';
    case GTM = 'GTM';
    case GUM = 'GUM';
    case GUY = 'GUY';
    case HKG = 'HKG';
    case HND = 'HND';
    case HRV = 'HRV';
    case HTI = 'HTI';
    case HUN = 'HUN';
    case IDN = 'IDN';
    case IMN = 'IMN';
    case IND = 'IND';
    case IRL = 'IRL';
    case IRN = 'IRN';
    case IRQ = 'IRQ';
    case ISL = 'ISL';
    case ISR = 'ISR';
    case ITA = 'ITA';
    case JAM = 'JAM';
    case JEY = 'JEY';
    case JOR = 'JOR';
    case JPN = 'JPN';
    case KAZ = 'KAZ';
    case KEN = 'KEN';
    case KGZ = 'KGZ';
    case KHM = 'KHM';
    case KIR = 'KIR';
    case KNA = 'KNA';
    case KOR = 'KOR';
    case KWT = 'KWT';
    case LAO = 'LAO';
    case LBN = 'LBN';
    case LBR = 'LBR';
    case LBY = 'LBY';
    case LCA = 'LCA';
    case LIE = 'LIE';
    case LKA = 'LKA';
    case LSO = 'LSO';
    case LTU = 'LTU';
    case LUX = 'LUX';
    case LVA = 'LVA';
    case MAC = 'MAC';
    case MAR = 'MAR';
    case MCO = 'MCO';
    case MDA = 'MDA';
    case MDG = 'MDG';
    case MDV = 'MDV';
    case MEX = 'MEX';
    case MHL = 'MHL';
    case MKD = 'MKD';
    case MLI = 'MLI';
    case MLT = 'MLT';
    case MMR = 'MMR';
    case MNE = 'MNE';
    case MNG = 'MNG';
    case MNP = 'MNP';
    case MOZ = 'MOZ';
    case MRT = 'MRT';
    case MSR = 'MSR';
    case MUS = 'MUS';
    case MWI = 'MWI';
    case MYS = 'MYS';
    case NAM = 'NAM';
    case NER = 'NER';
    case NGA = 'NGA';
    case NIC = 'NIC';
    case NLD = 'NLD';
    case NOR = 'NOR';
    case NPL = 'NPL';
    case NRU = 'NRU';
    case NZL = 'NZL';
    case OMN = 'OMN';
    case PAK = 'PAK';
    case PAN = 'PAN';
    case PER = 'PER';
    case PHL = 'PHL';
    case PLW = 'PLW';
    case PNG = 'PNG';
    case POL = 'POL';
    case PRI = 'PRI';
    case PRK = 'PRK';
    case PRT = 'PRT';
    case PRY = 'PRY';
    case PSE = 'PSE';
    case QAT = 'QAT';
    case ROU = 'ROU';
    case RUS = 'RUS';
    case RWA = 'RWA';
    case SAU = 'SAU';
    case SDN = 'SDN';
    case SEN = 'SEN';
    case SGP = 'SGP';
    case SLB = 'SLB';
    case SLE = 'SLE';
    case SLV = 'SLV';
    case SMR = 'SMR';
    case SOM = 'SOM';
    case SRB = 'SRB';
    case SSD = 'SSD';
    case STP = 'STP';
    case SUR = 'SUR';
    case SVK = 'SVK';
    case SVN = 'SVN';
    case SWE = 'SWE';
    case SWZ = 'SWZ';
    case SYC = 'SYC';
    case SYR = 'SYR';
    case TCA = 'TCA';
    case TCD = 'TCD';
    case TGO = 'TGO';
    case THA = 'THA';
    case TJK = 'TJK';
    case TKM = 'TKM';
    case TLS = 'TLS';
    case TON = 'TON';
    case TTO = 'TTO';
    case TUN = 'TUN';
    case TUR = 'TUR';
    case TUV = 'TUV';
    case TWN = 'TWN';
    case TZA = 'TZA';
    case UGA = 'UGA';
    case UKR = 'UKR';
    case URY = 'URY';
    case USA = 'USA';
    case UZB = 'UZB';
    case VAT = 'VAT';
    case VCT = 'VCT';
    case VEN = 'VEN';
    case VGB = 'VGB';
    case VIR = 'VIR';
    case VNM = 'VNM';
    case VUT = 'VUT';
    case WSM = 'WSM';
    case XKX = 'XKX';
    case YEM = 'YEM';
    case ZAF = 'ZAF';
    case ZMB = 'ZMB';
    case ZWE = 'ZWE';

    /**
     * Country names taken from FCDO[1], or ISO[2] where FCDO not available
     *
     * 1: https://assets.publishing.service.gov.uk/media/65fd8475f1d3a0001132adf4/FCDO_Geographical_Names_Index_March_2024.csv/preview
     * 2: https://www.iso.org/obp/ui/#search
     */
    public function translate(): string
    {
        /**
         * @psalm-suppress UnhandledMatchCondition
         * Due to Psalm bug: https://github.com/vimeo/psalm/issues/8464
         */
        return match ($this) {
            self::AFG => 'Afghanistan',
            self::AGO => 'Angola',
            self::AIA => 'Anguilla',
            self::ALB => 'Albania',
            self::AND => 'Andorra',
            self::ARE => 'United Arab Emirates',
            self::ARG => 'Argentina',
            self::ARM => 'Armenia',
            self::ASM => 'American Samoa',
            self::ATG => 'Antigua and Barbuda',
            self::AUS => 'Australia',
            self::AUT => 'Austria',
            self::AZE => 'Azerbaijan',
            self::BDI => 'Burundi',
            self::BEL => 'Belgium',
            self::BEN => 'Benin',
            self::BFA => 'Burkina Faso',
            self::BGD => 'Bangladesh',
            self::BGR => 'Bulgaria',
            self::BHR => 'Bahrain',
            self::BHS => 'The Bahamas',
            self::BIH => 'Bosnia and Herzegovina',
            self::BLR => 'Belarus',
            self::BLZ => 'Belize',
            self::BMU => 'Bermuda',
            self::BOL => 'Bolivia',
            self::BRA => 'Brazil',
            self::BRB => 'Barbados',
            self::BRN => 'Brunei',
            self::BTN => 'Bhutan',
            self::BWA => 'Botswana',
            self::CAF => 'Central African Republic',
            self::CAN => 'Canada',
            self::CHE => 'Switzerland',
            self::CHL => 'Chile',
            self::CHN => 'China',
            self::CIV => 'Ivory Coast',
            self::CMR => 'Cameroon',
            self::COD => 'Congo (Democratic Republic)',
            self::COG => 'Congo',
            self::COL => 'Colombia',
            self::COM => 'Comoros',
            self::CPV => 'Cape Verde',
            self::CRI => 'Costa Rica',
            self::CUB => 'Cuba',
            self::CYM => 'Cayman Islands (the)',
            self::CYP => 'Cyprus',
            self::CZE => 'Czechia',
            self::DEU => 'Germany',
            self::DJI => 'Djibouti',
            self::DMA => 'Dominica',
            self::DNK => 'Denmark',
            self::DOM => 'Dominican Republic',
            self::DZA => 'Algeria',
            self::ECU => 'Ecuador',
            self::EGY => 'Egypt',
            self::ERI => 'Eritrea',
            self::ESP => 'Spain',
            self::EST => 'Estonia',
            self::ETH => 'Ethiopia',
            self::FIN => 'Finland',
            self::FJI => 'Fiji',
            self::FRA => 'France',
            self::FRO => 'Faroe Islands (the)',
            self::FSM => 'Federated States of Micronesia',
            self::GAB => 'Gabon',
            self::GBR => 'United Kingdom',
            self::GEO => 'Georgia',
            self::GGY => 'Guernsey',
            self::GHA => 'Ghana',
            self::GIB => 'Gibraltar',
            self::GIN => 'Guinea',
            self::GMB => 'The Gambia',
            self::GNB => 'Guinea-Bissau',
            self::GNQ => 'Equatorial Guinea',
            self::GRC => 'Greece',
            self::GRD => 'Grenada',
            self::GRL => 'Greenland',
            self::GTM => 'Guatemala',
            self::GUM => 'Guam',
            self::GUY => 'Guyana',
            self::HKG => 'Hong Kong',
            self::HND => 'Honduras',
            self::HRV => 'Croatia',
            self::HTI => 'Haiti',
            self::HUN => 'Hungary',
            self::IDN => 'Indonesia',
            self::IMN => 'Isle of Man',
            self::IND => 'India',
            self::IRL => 'Ireland',
            self::IRN => 'Iran',
            self::IRQ => 'Iraq',
            self::ISL => 'Iceland',
            self::ISR => 'Israel',
            self::ITA => 'Italy',
            self::JAM => 'Jamaica',
            self::JEY => 'Jersey',
            self::JOR => 'Jordan',
            self::JPN => 'Japan',
            self::KAZ => 'Kazakhstan',
            self::KEN => 'Kenya',
            self::KGZ => 'Kyrgyzstan',
            self::KHM => 'Cambodia',
            self::KIR => 'Kiribati',
            self::KNA => 'St Kitts and Nevis',
            self::KOR => 'South Korea',
            self::KWT => 'Kuwait',
            self::LAO => 'Laos',
            self::LBN => 'Lebanon',
            self::LBR => 'Liberia',
            self::LBY => 'Libya',
            self::LCA => 'St Lucia',
            self::LIE => 'Liechtenstein',
            self::LKA => 'Sri Lanka',
            self::LSO => 'Lesotho',
            self::LTU => 'Lithuania',
            self::LUX => 'Luxembourg',
            self::LVA => 'Latvia',
            self::MAC => 'Macao',
            self::MAR => 'Morocco',
            self::MCO => 'Monaco',
            self::MDA => 'Moldova',
            self::MDG => 'Madagascar',
            self::MDV => 'Maldives',
            self::MEX => 'Mexico',
            self::MHL => 'Marshall Islands',
            self::MKD => 'North Macedonia',
            self::MLI => 'Mali',
            self::MLT => 'Malta',
            self::MMR => 'Myanmar (Burma)',
            self::MNE => 'Montenegro',
            self::MNG => 'Mongolia',
            self::MNP => 'Northern Mariana Islands (the)',
            self::MOZ => 'Mozambique',
            self::MRT => 'Mauritania',
            self::MSR => 'Montserrat',
            self::MUS => 'Mauritius',
            self::MWI => 'Malawi',
            self::MYS => 'Malaysia',
            self::NAM => 'Namibia',
            self::NER => 'Niger',
            self::NGA => 'Nigeria',
            self::NIC => 'Nicaragua',
            self::NLD => 'Netherlands',
            self::NOR => 'Norway',
            self::NPL => 'Nepal',
            self::NRU => 'Nauru',
            self::NZL => 'New Zealand',
            self::OMN => 'Oman',
            self::PAK => 'Pakistan',
            self::PAN => 'Panama',
            self::PER => 'Peru',
            self::PHL => 'Philippines',
            self::PLW => 'Palau',
            self::PNG => 'Papua New Guinea',
            self::POL => 'Poland',
            self::PRI => 'Puerto Rico',
            self::PRK => 'North Korea',
            self::PRT => 'Portugal',
            self::PRY => 'Paraguay',
            self::PSE => 'Palestine',
            self::QAT => 'Qatar',
            self::ROU => 'Romania',
            self::RUS => 'Russia',
            self::RWA => 'Rwanda',
            self::SAU => 'Saudi Arabia',
            self::SDN => 'Sudan',
            self::SEN => 'Senegal',
            self::SGP => 'Singapore',
            self::SLB => 'Solomon Islands',
            self::SLE => 'Sierra Leone',
            self::SLV => 'El Salvador',
            self::SMR => 'San Marino',
            self::SOM => 'Somalia',
            self::SRB => 'Serbia',
            self::SSD => 'South Sudan',
            self::STP => 'Sao Tome and Principe',
            self::SUR => 'Suriname',
            self::SVK => 'Slovakia',
            self::SVN => 'Slovenia',
            self::SWE => 'Sweden',
            self::SWZ => 'Eswatini',
            self::SYC => 'Seychelles',
            self::SYR => 'Syria',
            self::TCA => 'Turks and Caicos Islands (the)',
            self::TCD => 'Chad',
            self::TGO => 'Togo',
            self::THA => 'Thailand',
            self::TJK => 'Tajikistan',
            self::TKM => 'Turkmenistan',
            self::TLS => 'East Timor',
            self::TON => 'Tonga',
            self::TTO => 'Trinidad and Tobago',
            self::TUN => 'Tunisia',
            self::TUR => 'Turkey',
            self::TUV => 'Tuvalu',
            self::TWN => 'Taiwan (Province of China)',
            self::TZA => 'Tanzania',
            self::UGA => 'Uganda',
            self::UKR => 'Ukraine',
            self::URY => 'Uruguay',
            self::USA => 'United States',
            self::UZB => 'Uzbekistan',
            self::VAT => 'Vatican City',
            self::VCT => 'St Vincent',
            self::VEN => 'Venezuela',
            self::VGB => 'Virgin Islands (British)',
            self::VIR => 'Virgin Islands (U.S.)',
            self::VNM => 'Vietnam',
            self::VUT => 'Vanuatu',
            self::WSM => 'Samoa',
            self::XKX => 'Kosovo',
            self::YEM => 'Yemen',
            self::ZAF => 'South Africa',
            self::ZMB => 'Zambia',
            self::ZWE => 'Zimbabwe',
        };
    }

    public function isEUOrEEA(): bool
    {
        return in_array($this, self::EU_EEA_COUNTRY_CODES);
    }
}

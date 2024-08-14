<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\Listener as AuthListener;
use Application\Auth\ListenerFactory as AuthListenerFactory;
use Application\Factories\ConfigHelperFactory;
use Application\Factories\LocalisationHelperFactory;
use Application\Factories\LoggerFactory;
use Application\Factories\OpgApiServiceFactory;
use Application\Factories\SiriusApiServiceFactory;
use Application\Helpers\ConfigHelper;
use Application\Helpers\LocalisationHelper;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Application\Views\TwigExtension;
use Application\Views\TwigExtensionFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Psr\Log\LoggerInterface;
use Twig\Extension\DebugExtension;

$prefix = getenv("PREFIX");
if (! is_string($prefix)) {
    $prefix = '';
}

return [
    'router' => [
        'routes' => [
            'root' => [
                'type' => Segment::class,
                'options' => [
                    'route' => $prefix,
                ],
                'child_routes' => [
                    'home' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'start' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/start',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'start',
                            ],
                        ],
                    ],
                    'donor_id_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/donor-id-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorIdCheck',
                            ],
                        ],
                    ],
                    'donor_lpa_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/donor-lpa-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorLpaCheck',
                            ],
                        ],
                    ],
                    'how_donor_confirms' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/how-will-donor-confirm',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'howWillDonorConfirm',
                            ],
                        ],
                    ],
                    'donor_details_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/donor-details-match-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorDetailsMatchCheck',
                            ],
                        ],
                    ],
                    'address_verification' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/address_verification',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'addressVerification',
                            ],
                        ],
                    ],
                    'national_insurance_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/national-insurance-number',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'nationalInsuranceNumber',
                            ],
                        ],
                    ],
                    'driving_licence_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/driving-licence-number',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'drivingLicenceNumber',
                            ],
                        ],
                    ],
                    'passport_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/passport-number',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'passportNumber',
                            ],
                        ],
                    ],
                    'id_verify_questions' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/id-verify-questions',
                            'defaults' => [
                                'controller' => Controller\KbvController::class,
                                'action' => 'idVerifyQuestions',
                            ],
                        ],
                    ],
                    'identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                    'identity_check_failed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/identity-check-failed',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'identityCheckFailed',
                            ],
                        ],
                    ],
                    'thin_file_failure' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/thin-file-failure',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'thinFileFailure',
                            ],
                        ],
                    ],
                    'proving_identity' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/proving-identity',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'provingIdentity',
                            ],
                        ],
                    ],
                    'post_office_documents' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/post-office-documents',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'postOfficeDocuments',
                            ],
                        ],
                    ],
                    'po_do_details_match' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-do-details-match',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'doDetailsMatch',
                            ],
                        ],
                    ],
                    'po_donor_lpa_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/post-office-donor-lpa-check',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'donorLpaCheck',
                            ],
                        ],
                    ],
                    'find_post_office_branch' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/find-post-office-branch',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'findPostOfficeBranch',
                            ],
                        ],
                    ],
                    'confirm_post_office' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/confirm-post-office',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'confirmPostOffice',
                            ],
                        ],
                    ],
                    'what_happens_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/what-happens-next',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'whatHappensNext',
                            ],
                        ],
                    ],
                    'post_office_route_not_available' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/post-office-route-not-available',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'postOfficeRouteNotAvailable',
                            ],
                        ],
                    ],
                    'cp_how_cp_confirms' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/how-will-cp-confirm',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'howWillCpConfirm',
                            ],
                        ],
                    ],
                    'cp_post_office_documents' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/post-office-documents',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'postOfficeDocuments',
                            ],
                        ],
                    ],
                    'cp_choose_country' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/choose-country',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'chooseCountry',
                            ],
                        ],
                    ],
                    'cp_choose_country_id' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/choose-country-id',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'chooseCountryId',
                            ],
                        ],
                    ],
                    'cp_name_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/name-match-check',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'nameMatchCheck',
                            ],
                        ],
                    ],
                    'cp_confirm_lpas' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/confirm-lpas',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmLpas',
                            ],
                        ],
                    ],
                    'cp_add_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/add-lpa',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'addLpa',
                            ],
                        ],
                    ],
                    'cp_confirm_dob' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/confirm-dob',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmDob',
                            ],
                        ],
                    ],
                    'cp_confirm_address' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/confirm-address',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmAddress',
                            ],
                        ],
                    ],
                    'cp_national_insurance_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/national-insurance-number',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'nationalInsuranceNumber',
                            ],
                        ],
                    ],
                    'cp_driving_licence_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/driving-licence-number',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'drivingLicenceNumber',
                            ],
                        ],
                    ],
                    'cp_passport_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/passport-number',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'passportNumber',
                            ],
                        ],
                    ],
                    'cp_id_verify_questions' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/id-verify-questions',
                            'defaults' => [
                                'controller' => Controller\KbvController::class,
                                'action' => 'idVerifyQuestions',
                            ],
                        ],
                    ],
                    'cp_identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                    'cp_identity_check_failed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/identity-check-failed',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'identityCheckFailed',
                            ],
                        ],
                    ],
                    'cp_enter_postcode' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/enter-postcode',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'enterPostcode',
                            ],
                        ],
                    ],
                    'cp_enter_address_manual' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/enter-address-manual',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'enterAddressManual',
                            ],
                        ],
                    ],
                    'cp_select_address' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/select-address/:postcode',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'selectAddress',
                            ],
                        ],
                    ],
                    'remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'cp_remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'po_remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'abandon_flow' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/abandon-flow',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'abandonFlow',
                            ],
                        ],
                    ],
                    'donor_choose_country' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-choose-country',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'chooseCountry',
                            ],
                        ],
                    ],
                    'donor_choose_country_id' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-choose-country-id',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'chooseCountryId',
                            ],
                        ],
                    ],
                    'cp_find_post_office_branch' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/find-post-office-branch',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'findPostOfficeBranch',
                            ],
                        ],
                    ],
                    'cp_confirm_post_office' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/confirm-post-office',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'confirmPostOffice',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\CPFlowController::class => LazyControllerAbstractFactory::class,
            Controller\DonorFlowController::class => LazyControllerAbstractFactory::class,
            Controller\IndexController::class => LazyControllerAbstractFactory::class,
            Controller\KbvController::class => LazyControllerAbstractFactory::class,
            Controller\PostOfficeFlowController::class => LazyControllerAbstractFactory::class,
        ],
    ],
    'listeners' => [
        AuthListener::class
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'layout' => 'layout/plain',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'service_manager' => [
        'aliases' => [
            Contracts\OpgApiServiceInterface::class => Services\OpgApiService::class,
        ],
        'factories' => [
            AuthListener::class => AuthListenerFactory::class,
            LoggerInterface::class => LoggerFactory::class,
            OpgApiService::class => OpgApiServiceFactory::class,
            SiriusApiService::class => SiriusApiServiceFactory::class,
            TwigExtension::class => TwigExtensionFactory::class,
            LocalisationHelper::class => LocalisationHelperFactory::class,
        ],
    ],
    'zend_twig' => [
        'extensions' => [
            TwigExtension::class,
            DebugExtension::class,
        ],
        'environment' => [
            'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        ],
    ],
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
        'acceptable_nations_for_id_documents' => [
            'AFG' => 'Afghanistan',
            'ALB' => 'Albania',
            'DZA' => 'Algeria',
            'ASM' => 'American Samoa',
            'AND' => 'Andorra',
            'AGO' => 'Angola',
            'AIA' => 'Anguilla',
            'ATG' => 'Antigua and Barbuda',
            'ARG' => 'Argentina',
            'ARM' => 'Armenia',
            'AUS' => 'Australia',
            'AUT' => 'Austria',
            'AZE' => 'Azerbaijan',
            'BHS' => 'Bahamas',
            'BHR' => 'Bahrain',
            'BGD' => 'Bangladesh',
            'BRB' => 'Barbados',
            'BLR' => 'Belarus',
            'BEL' => 'Belgium',
            'BLZ' => 'Belize',
            'BEN' => 'Benin',
            'BMU' => 'Bermuda',
            'BTN' => 'Bhutan',
            'BOL' => 'Bolivia',
            'BIH' => 'Bosnia & Herzegovina',
            'BWA' => 'Botswana',
            'BRA' => 'Brazil',
            'IOT' => 'British Indian Ocean Territory',
            'VGB' => 'British Virgin Islands',
            'BRN' => 'Brunei',
            'BGR' => 'Bulgaria',
            'BFA' => 'Burkina Faso',
            'BDI' => 'Burundi',
            'KHM' => 'Cambodia',
            'CMR' => 'Cameroon',
            'CAN' => 'Canada',
            'CPV' => 'Cape Verde',
            'BQ' => 'Caribbean Netherlands',
            'CYM' => 'Cayman Islands',
            'CAF' => 'Central African Republic',
            'TCD' => 'Chad',
            'CHL' => 'Chile',
            'CHN' => 'China',
            'CXR' => 'Christmas Island',
            'CCK' => 'Cocos (Keeling) Islands',
            'COL' => 'Colombia',
            'COM' => 'Comoros',
            'CRI' => 'Costa Rica',
            'HRV' => 'Croatia',
            'CUB' => 'Cuba',
            'CUW' => 'Curacao',
            'CYP' => 'Cyprus',
            'CZE' => 'Czechia',
            'COD' => 'Democratic Republic of the Congo',
            'DNK' => 'Denmark',
            'DJI' => 'Djibouti',
            'DMA' => 'Dominica',
            'DOM' => 'Dominican Republic',
            'TLS' => 'East Timor',
            'ECU' => 'Ecuador',
            'EGY' => 'Egypt',
            'SLV' => 'El Salvador',
            'GNQ' => 'Equatorial Guinea',
            'ERI' => 'Eritrea',
            'EST' => 'Estonia',
            'ETH' => 'Ethiopia',
            'FRO' => 'Faroe Islands',
            'FJI' => 'Fiji',
            'FIN' => 'Finland',
            'FRA' => 'France',
            'GUF' => 'French Guiana',
            'PYF' => 'French Polynesia',
            'GAB' => 'Gabon',
            'GMB' => 'Gambia',
            'DEU' => 'Germany',
            'GHA' => 'Ghana',
            'GIB' => 'Gibraltar',
            'GRC' => 'Greece',
            'GRL' => 'Greenland',
            'GRD' => 'Grenada',
            'GLP' => 'Guadeloupe',
            'GUM' => 'Guam',
            'GTM' => 'Guatemala',
            'GGY' => 'Guernsey',
            'GIN' => 'Guinea',
            'GNB' => 'Guinea Bissau',
            'GUY' => 'Guyana',
            'HTI' => 'Haiti',
            'HND' => 'Honduras',
            'HKG' => 'Hong Kong',
            'HUN' => 'Hungary',
            'ISL' => 'Iceland',
            'IND' => 'India',
            'IDN' => 'Indonesia',
            'IRN' => 'Iran',
            'IRQ' => 'Iraq',
            'IRL' => 'Ireland',
            'IMN' => 'Isle of Man',
            'ISR' => 'Israel',
            'ITA' => 'Italy',
            'CIV' => 'Ivory Coast',
            'JAM' => 'Jamaica',
            'JPN' => 'Japan',
            'JEY' => 'Jersey',
            'JOR' => 'Jordan',
            'KAZ' => 'Kazakhstan',
            'KEN' => 'Kenya',
            'KIR' => 'Kiribati',
            'XKX' => 'Kosovo',
            'KWT' => 'Kuwait',
            'KGZ' => 'Kyrgystan',
            'LAO' => 'Laos',
            'LVA' => 'Latvia',
            'LBN' => 'Lebanon',
            'LSD' => 'Lesotho',
            'LBR' => 'Liberia',
            'LBY' => 'Libya',
            'LIE' => 'Liechtenstein',
            'LTU' => 'Lithuania',
            'LUX' => 'Luxembourg',
            'MAC' => 'Macau',
            'MKD' => 'Macedonia',
            'MDG' => 'Madagascar',
            'MWI' => 'Malawi',
            'MYS' => 'Malaysia',
            'MDV' => 'Maldives',
            'MLI' => 'Mali',
            'MLT' => 'Malta',
            'MHL' => 'Marshall Islands',
            'MTQ' => 'Martinique',
            'MRT' => 'Mauritania',
            'MUS' => 'Mauritius',
            'MYT' => 'Mayotte',
            'MEX' => 'Mexico',
            'FSM' => 'Micronesia',
            'MDA' => 'Moldova',
            'MCO' => 'Monaco',
            'MNG' => 'Mongolia',
            'MNE' => 'Montenegro',
            'MSR' => 'Montserrat',
            'MAR' => 'Morocco',
            'MOZ' => 'Mozambique',
            'MMR' => 'Myanmar',
            'NAM' => 'Namibia',
            'NRU' => 'Nauru',
            'NPL' => 'Nepal',
            'NLD' => 'Netherlands',
            'NCL' => 'New Caledonia',
            'NZL' => 'New Zealand',
            'NIC' => 'Nicaragua',
            'NER' => 'Niger',
            'NGA' => 'Nigeria',
            'NIU' => 'Niue',
            'NFK' => 'Norfolk Island',
            'PRK' => 'North Korea',
            'MNP' => 'Northern Mariana Islands',
            'NOR' => 'Norway',
            'PMN' => 'Oman',
            'PAK' => 'Pakistan',
            'PLW' => 'Palau',
            'PSE' => 'Palestine',
            'PAN' => 'Panana',
            'PNG' => 'Papua New Guinea',
            'PRY' => 'Paraguay',
            'PER' => 'Peru',
            'PHL' => 'Philippines',
            'POL' => 'Poland',
            'PRT' => 'Portugal',
            'PRI' => 'Puerto Rico',
            'QAT' => 'Qatar',
            'COG' => 'Republic of the Congo',
            'REU' => 'Reunion',
            'ROU' => 'Romania',
            'RUS' => 'Russia',
            'RWA' => 'Rwanda',
            'KNA' => 'St Kitts & Nevis',
            'LCA' => 'St Lucia',
            'VCT' => 'St Vincent & the Grenadines',
            'WSM' => 'Samoa',
            'SMR' => 'San Marino',
            'STP' => 'Sao Tome 7 Principe',
            'SAU' => 'Saudi Arabia',
            'SEN' => 'Senegal',
            'SRB' => 'Serbia',
            'SYC' => 'Seychelles',
            'SLE' => 'Sierra Leone',
            'SGP' => 'Singapore',
            'ZXM' => 'Sint Maarten',
            'SKV' => 'Slovakia',
            'SVN' => 'Slovenia',
            'SLB' => 'Solomon Islands',
            'SOM' => 'Somalia',
            'ZAF' => 'South Africa',
            'KOR' => 'South Korea',
            'SSD' => 'South Sudan',
            'ESP' => 'Spain',
            'LKA' => 'Sri Lanka',
            'BLM' => 'St Bathelemy',
            'SHN' => 'St Helena',
            'MAF' => 'St Martin',
            'SPM' => 'St Pierre & Miquelon',
            'SDN' => 'Sudan',
            'SUR' => 'Suriname',
            'SJM' => 'Svalbard & Jan Meyen',
            'SWZ' => 'Swaziland',
            'SWE' => 'Sweden',
            'CHE' => 'Switzerland',
            'SYR' => 'Syria',
            'TWN' => 'Taiwan',
            'TJK' => 'Tajikistan',
            'TZA' => 'Tanzania',
            'THA' => 'Thailand',
            'TGO' => 'Togo',
            'TKL' => 'Tokelau',
            'TON' => 'Tonga',
            'TTO' => 'Trinidad & Tobago',
            'TAA' => 'Tristan de Cunha',
            'TUN' => 'Tunisia',
            'TUR' => 'Turkey',
            'TKM' => 'Turkmenistan',
            'TCA' => 'Turks & Caicos Islands',
            'TUV' => 'Tuvalu',
            'VIR' => 'US Virgin Islands',
            'UGA' => 'Uganda',
            'UKR' => 'Ukraine',
            'ARE' => 'United Arab Emirates',
            'GBR' => 'United Kingdom',
            'USA' => 'United States',
            'URY' => 'Uruguay',
            'UZB' => 'Uzbekistan',
            'VUT' => 'Vanuatu',
            'VAT' => 'Vatican',
            'VEN' => 'Venezuela',
            'VNM' => 'Vietnam',
            'WLF' => 'Wallis & Futuna',
            'ESH' => 'Western Sahara',
            'YEM' => 'Yemen',
            'ZMB' => 'Zambia',
            'ZWE' => 'Zimbabwe',
        ],
        "supported_countries_documents" => [
            [
                "code" => "AFG",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ALB",
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
                ]
            ],
            [
                "code" => "DZA",
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
                ]
            ],
            [
                "code" => "ASM",
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
                ]
            ],
            [
                "code" => "AND",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "AGO",
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
                ]
            ],
            [
                "code" => "AIA",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ATG",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ARG",
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
                ]
            ],
            [
                "code" => "ARM",
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
                ]
            ],
            [
                "code" => "AUS",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
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
                "code" => "AZE",
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
                ]
            ],
            [
                "code" => "BHS",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BHR",
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
                ]
            ],
            [
                "code" => "BGD",
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
                ]
            ],
            [
                "code" => "BRB",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BLR",
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
                ]
            ],
            [
                "code" => "BEL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "BLZ",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BEN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BGR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "BHR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BHS",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BIH",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BOL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BMU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BRA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BRN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BTN",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "BWA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CAF",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CAN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "CHE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CHL",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CIV",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CMR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "COD",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "COL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "COM",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CPV",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CRP",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],

            [
                "code" => "CUB",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CYM",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "CYP",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "CZE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "DEU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "DJT",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "DMA",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "DNK",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-01-01"
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
                "code" => "DOM",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-01-01"
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
                ]
            ],
            [
                "code" => "ECU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "EGY",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ERI",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ESP",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2006-06-01"
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
                "code" => "EST",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "ETH",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "FIN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2011-05-01"
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
                "code" => "FJI",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "FRA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-09-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "FRO",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "FSM",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GAB",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GBR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
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
                "code" => "GGY",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GHA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GIB",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GIN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GMB",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GNB",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GNQ",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GRC",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "GRD",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GRL",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GTM",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-07-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GUM",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-07-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "GUY",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "HKG",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "HND",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-07-01"
                        ]
                    ],
                ]
            ],
            [
                "code" => "HRV",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-07-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "HTI",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "HUN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2020-01-01"
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
                "code" => "IDN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2020-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "IMN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "IND",
                "supported_documents" => [
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "IRL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2015-09-01"
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
                "code" => "IRN",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "IRQ",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],

            [
                "code" => "ISL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ISR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2015-09-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "ITA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
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
                "code" => "JAM",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "JEY",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "JOR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "JPN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KAZ",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KEN",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KGZ",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KHM",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KIR",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KNA",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KOR",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "KWT",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LAO",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LBN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LBR",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LBY",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LCA",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LIE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "LTU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "LUX",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "LVA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "MAC",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MAR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MCO",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MDA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MDG",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MDV",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MEX",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MHL",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MKD",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MLI",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MLT",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "MMR",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MNE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MNG",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MNP",
                "supported_documents" => [
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "MOZ",
                "supported_documents" => [
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                ]
            ],
            [
                "code" => "NLD",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2014-03-01"
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
                "code" => "POL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2001-01-01"
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
                "code" => "PRT",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2007-01-01"
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
                "code" => "ROU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "SVN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
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
                "code" => "SWE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2007-12-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-06-01"
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
];

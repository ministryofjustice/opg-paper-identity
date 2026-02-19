<?php

declare(strict_types=1);

use Application\Handler;
use Mezzio\Application;

// phpcs:disable Generic.Files.LineLength
return function (Application $app): void {
    $prefix = getenv("PREFIX");
    if (! is_string($prefix)) {
        $prefix = '';
    }

    // Health checks
    $app->get($prefix . '/health-check', Handler\HealthCheck\StatusHandler::class);
    $app->get($prefix . '/health-check/service', Handler\HealthCheck\ServiceStatusHandler::class);

    // Common pages
    $app->get($prefix . '/start', Handler\StartHandler::class, 'start');
    $app->route($prefix . '/:uuid/how-will-you-confirm', Handler\HowConfirm\HowWillYouConfirmHandler::class, ['GET', 'POST'], 'how_will_you_confirm');
    $app->route($prefix . '/:uuid/abandon-flow', Handler\AbandonFlowHandler::class, ['GET', 'POST'], 'abandon_flow');

    // Document check flow
    $app->route($prefix . '/:uuid/national-insurance-number', Handler\DocumentCheck\NationalInsuranceNumberHandler::class, ['GET', 'POST'], 'national_insurance_number');
    $app->route($prefix . '/:uuid/driving-licence-number', Handler\DocumentCheck\DrivingLicenceNumberHandler::class, ['GET', 'POST'], 'driving_licence_number');
    $app->route($prefix . '/:uuid/passport-number', Handler\DocumentCheck\PassportNumberHandler::class, ['GET', 'POST'], 'passport_number');

    // KBV flow
    $app->route($prefix . '/:uuid/id-verify-questions', Handler\Kbv\QuestionsHandler::class, ['GET', 'POST'], 'id_verify_questions');
    $app->get($prefix . '/:uuid/identity-check-failed', Handler\Kbv\IdentityCheckFailedHandler::class, 'identity_check_failed');

    // Court of protection flow
    $app->route($prefix . '/:uuid/court-of-protection', Handler\CourtOfProtection\RegisterHandler::class, ['GET', 'POST'], 'court_of_protection');
    $app->get($prefix . '/:uuid/court-of-protection-what-next', Handler\CourtOfProtection\WhatNextHandler::class, 'court_of_protection_what_next');

    // Post office flow
    $app->route($prefix . '/:uuid/find-post-office-branch', Handler\PostOffice\FindPostOfficeBranchHandler::class, ['GET', 'POST'], 'find_post_office_branch');
    $app->get($prefix . '/:uuid/post-office-what-happens-next', Handler\PostOffice\WhatHappensNextHandler::class, 'po_what_happens_next');
    $app->route($prefix . '/:uuid/post-office-documents', Handler\PostOffice\ChooseUKDocumentHandler::class, ['GET', 'POST'], 'post_office_documents');
    $app->route($prefix . '/:uuid/po-choose-country', Handler\PostOffice\ChooseCountryHandler::class, ['GET', 'POST'], 'po_choose_country');
    $app->route($prefix . '/:uuid/po-choose-country-id', Handler\PostOffice\ChooseInternationalDocumentHandler::class, ['GET', 'POST'], 'po_choose_country_id');
    $app->get($prefix . '/:uuid/post-office-route-not-available', Handler\PostOffice\RouteNotAvailableHandler::class, 'post_office_route_not_available');

    // Donor journey
    $app->route($prefix . '/:uuid/donor-lpa-check', Handler\Donor\LpaCheckHandler::class, ['GET', 'POST'], 'donor_lpa_check');
    $app->get($prefix . '/:uuid/donor-details-match-check', Handler\Donor\DonorDetailsMatchCheckHandler::class, 'donor_details_match_check');
    $app->get($prefix . '/:uuid/remove-lpa/:lpa', Handler\Donor\RemoveLpaHandler::class, 'remove_lpa');
    $app->route($prefix . '/:uuid/what-is-vouching', Handler\Donor\WhatIsVouchingHandler::class, ['GET', 'POST'], 'what_is_vouching');
    $app->get($prefix . '/:uuid/vouching-what-happens-next', Handler\Donor\VouchingWhatHappensNextHandler::class, 'vouching_what_happens_next');
    $app->get($prefix . '/:uuid/thin-file-failure', Handler\Donor\ThinFileFailureHandler::class, 'thin_file_failure');
    $app->route($prefix . '/:uuid/identity-check-passed', Handler\Donor\IdentityCheckPassedHandler::class, ['GET', 'POST'], 'identity_check_passed');

    // Certificate provide journey
    $app->get($prefix . '/:uuid/cp/name-match-check', Handler\CertificateProvider\NameMatchCheckHandler::class, 'cp_name_match_check');
    $app->get($prefix . '/:uuid/cp/confirm-lpas', Handler\CertificateProvider\ConfirmLpasHandler::class, 'cp_confirm_lpas');
    $app->route($prefix . '/:uuid/cp/add-lpa', Handler\CertificateProvider\AddLpaHandler::class, ['GET', 'POST'], 'cp_add_lpa');
    $app->get($prefix . '/:uuid/cp/remove-lpa/:lpa', Handler\CertificateProvider\RemoveLpaHandler::class, 'cp_remove_lpa');
    $app->route($prefix . '/:uuid/cp/confirm-dob', Handler\CertificateProvider\ConfirmDobHandler::class, ['GET', 'POST'], 'cp_confirm_dob');
    $app->route($prefix . '/:uuid/cp/confirm-address', Handler\CertificateProvider\Address\ConfirmHandler::class, ['GET', 'POST'], 'cp_confirm_address');
    $app->route($prefix . '/:uuid/cp/enter-postcode', Handler\CertificateProvider\Address\PostcodeSearchHandler::class, ['GET', 'POST'], 'cp_enter_postcode');
    $app->route($prefix . '/:uuid/cp/select-address/:postcode', Handler\CertificateProvider\Address\SelectHandler::class, ['GET', 'POST'], 'cp_select_address');
    $app->route($prefix . '/:uuid/cp/enter-address-manual', Handler\CertificateProvider\Address\ManualEntryHandler::class, ['GET', 'POST'], 'cp_enter_address_manual');
    $app->get($prefix . '/:uuid/cp/identity-check-passed', Handler\CertificateProvider\IdentityCheckPassedHandler::class, 'cp_identity_check_passed');

    ## Vouching journey
    $app->route($prefix . '/:uuid/vouching/confirm-vouching', Handler\Voucher\ConfirmVouchingHandler::class, ['GET', 'POST'], 'confirm_vouching');
    $app->route($prefix . '/:uuid/vouching/voucher-name', Handler\Voucher\NameHandler::class, ['GET', 'POST'], 'voucher_name');
    $app->route($prefix . '/:uuid/vouching/voucher-dob', Handler\Voucher\DateOfBirthHandler::class, ['GET', 'POST'], 'voucher_dob');
    $app->route($prefix . '/:uuid/vouching/enter-postcode', Handler\Voucher\Address\PostcodeSearchHandler::class, ['GET', 'POST'], 'voucher_enter_postcode');
    $app->route($prefix . '/:uuid/vouching/select-address/:postcode', Handler\Voucher\Address\SelectHandler::class, ['GET', 'POST'], 'voucher_select_address');
    $app->route($prefix . '/:uuid/vouching/enter-address-manual', Handler\Voucher\Address\ManualEntryHandler::class, ['GET', 'POST'], 'voucher_enter_address_manual');
    $app->route($prefix . '/:uuid/vouching/confirm-donors', Handler\Voucher\ConfirmDonorsHandler::class, ['GET', 'POST'], 'voucher_confirm_donors');
    $app->route($prefix . '/:uuid/vouching/add-donor', Handler\Voucher\AddLpaHandler::class, ['GET', 'POST'], 'voucher_add_donor');
    $app->get($prefix . '/:uuid/vouching/remove-lpa/:lpa', Handler\Voucher\RemoveLpaHandler::class, 'voucher_remove_lpa');
    $app->route($prefix . '/:uuid/vouching/identity-check-passed', Handler\Voucher\IdentityCheckPassedHandler::class, ['GET', 'POST'], 'voucher_identity_check_passed');
};
// phpcs:enable

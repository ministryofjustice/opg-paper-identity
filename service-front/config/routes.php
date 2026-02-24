<?php

declare(strict_types=1);

use Application\Handler\HealthCheck\StatusHandler;
use Application\Handler\HealthCheck\ServiceStatusHandler;
use Application\Handler\StartHandler;
use Application\Handler\HowConfirm\HowWillYouConfirmHandler;
use Application\Handler\AbandonFlowHandler;
use Application\Handler\DocumentCheck\NationalInsuranceNumberHandler;
use Application\Handler\DocumentCheck\DrivingLicenceNumberHandler;
use Application\Handler\DocumentCheck\PassportNumberHandler;
use Application\Handler\Kbv\QuestionsHandler;
use Application\Handler\Kbv\IdentityCheckFailedHandler;
use Application\Handler\CourtOfProtection\RegisterHandler;
use Application\Handler\CourtOfProtection\WhatNextHandler;
use Application\Handler\PostOffice\FindPostOfficeBranchHandler;
use Application\Handler\PostOffice\WhatHappensNextHandler;
use Application\Handler\PostOffice\ChooseUKDocumentHandler;
use Application\Handler\PostOffice\ChooseCountryHandler;
use Application\Handler\PostOffice\ChooseInternationalDocumentHandler;
use Application\Handler\PostOffice\RouteNotAvailableHandler;
use Application\Handler\Donor\LpaCheckHandler;
use Application\Handler\Donor\DonorDetailsMatchCheckHandler;
use Application\Handler\Donor\RemoveLpaHandler;
use Application\Handler\Donor\WhatIsVouchingHandler;
use Application\Handler\Donor\VouchingWhatHappensNextHandler;
use Application\Handler\Donor\ThinFileFailureHandler;
use Application\Handler\Donor\IdentityCheckPassedHandler;
use Application\Handler\CertificateProvider\NameMatchCheckHandler;
use Application\Handler\CertificateProvider\ConfirmLpasHandler;
use Application\Handler\CertificateProvider\AddLpaHandler;
use Application\Handler\CertificateProvider\RemoveLpaHandler as CertificateProviderRemoveLpaHandler;
use Application\Handler\CertificateProvider\ConfirmDobHandler;
use Application\Handler\CertificateProvider\Address\ConfirmHandler;
use Application\Handler\CertificateProvider\Address\PostcodeSearchHandler;
use Application\Handler\CertificateProvider\Address\SelectHandler;
use Application\Handler\CertificateProvider\Address\ManualEntryHandler;
use Application\Handler\CertificateProvider\IdentityCheckPassedHandler as CertificateProviderIdentityCheckPassedHandler;
use Application\Handler\Voucher\ConfirmVouchingHandler;
use Application\Handler\Voucher\NameHandler;
use Application\Handler\Voucher\DateOfBirthHandler;
use Application\Handler\Voucher\Address\PostcodeSearchHandler as VoucherPostcodeSearchHandler;
use Application\Handler\Voucher\Address\SelectHandler as VoucherSelectHandler;
use Application\Handler\Voucher\Address\ManualEntryHandler as VoucherManualEntryHandler;
use Application\Handler\Voucher\ConfirmDonorsHandler;
use Application\Handler\Voucher\AddLpaHandler as VoucherAddLpaHandler;
use Application\Handler\Voucher\RemoveLpaHandler as VoucherRemoveLpaHandler;
use Application\Handler\Voucher\IdentityCheckPassedHandler as VoucherIdentityCheckPassedHandler;
use Mezzio\Application;

// phpcs:disable Generic.Files.LineLength
return function (Application $app): void {
    $prefix = getenv("PREFIX");
    if (! is_string($prefix)) {
        $prefix = '';
    }

    // Health checks
    $app->get($prefix . '/health-check', StatusHandler::class);
    $app->get($prefix . '/health-check/service', ServiceStatusHandler::class);

    // Common pages
    $app->get($prefix . '/start', StartHandler::class, 'start');
    $app->route($prefix . '/:uuid/how-will-you-confirm', HowWillYouConfirmHandler::class, ['GET', 'POST'], 'how_will_you_confirm');
    $app->route($prefix . '/:uuid/abandon-flow', AbandonFlowHandler::class, ['GET', 'POST'], 'abandon_flow');

    // Document check flow
    $app->route($prefix . '/:uuid/national-insurance-number', NationalInsuranceNumberHandler::class, ['GET', 'POST'], 'national_insurance_number');
    $app->route($prefix . '/:uuid/driving-licence-number', DrivingLicenceNumberHandler::class, ['GET', 'POST'], 'driving_licence_number');
    $app->route($prefix . '/:uuid/passport-number', PassportNumberHandler::class, ['GET', 'POST'], 'passport_number');

    // KBV flow
    $app->route($prefix . '/:uuid/id-verify-questions', QuestionsHandler::class, ['GET', 'POST'], 'id_verify_questions');
    $app->get($prefix . '/:uuid/identity-check-failed', IdentityCheckFailedHandler::class, 'identity_check_failed');

    // Court of protection flow
    $app->route($prefix . '/:uuid/court-of-protection', RegisterHandler::class, ['GET', 'POST'], 'court_of_protection');
    $app->get($prefix . '/:uuid/court-of-protection-what-next', WhatNextHandler::class, 'court_of_protection_what_next');

    // Post office flow
    $app->route($prefix . '/:uuid/find-post-office-branch', FindPostOfficeBranchHandler::class, ['GET', 'POST'], 'find_post_office_branch');
    $app->get($prefix . '/:uuid/post-office-what-happens-next', WhatHappensNextHandler::class, 'po_what_happens_next');
    $app->route($prefix . '/:uuid/post-office-documents', ChooseUKDocumentHandler::class, ['GET', 'POST'], 'post_office_documents');
    $app->route($prefix . '/:uuid/po-choose-country', ChooseCountryHandler::class, ['GET', 'POST'], 'po_choose_country');
    $app->route($prefix . '/:uuid/po-choose-country-id', ChooseInternationalDocumentHandler::class, ['GET', 'POST'], 'po_choose_country_id');
    $app->get($prefix . '/:uuid/post-office-route-not-available', RouteNotAvailableHandler::class, 'post_office_route_not_available');

    // Donor journey
    $app->route($prefix . '/:uuid/donor-lpa-check', LpaCheckHandler::class, ['GET', 'POST'], 'donor_lpa_check');
    $app->get($prefix . '/:uuid/donor-details-match-check', DonorDetailsMatchCheckHandler::class, 'donor_details_match_check');
    $app->get($prefix . '/:uuid/remove-lpa/:lpa', RemoveLpaHandler::class, 'remove_lpa');
    $app->route($prefix . '/:uuid/what-is-vouching', WhatIsVouchingHandler::class, ['GET', 'POST'], 'what_is_vouching');
    $app->get($prefix . '/:uuid/vouching-what-happens-next', VouchingWhatHappensNextHandler::class, 'vouching_what_happens_next');
    $app->get($prefix . '/:uuid/thin-file-failure', ThinFileFailureHandler::class, 'thin_file_failure');
    $app->route($prefix . '/:uuid/identity-check-passed', IdentityCheckPassedHandler::class, ['GET', 'POST'], 'identity_check_passed');

    // Certificate provider journey
    $app->get($prefix . '/:uuid/cp/name-match-check', NameMatchCheckHandler::class, 'cp_name_match_check');
    $app->get($prefix . '/:uuid/cp/confirm-lpas', ConfirmLpasHandler::class, 'cp_confirm_lpas');
    $app->route($prefix . '/:uuid/cp/add-lpa', AddLpaHandler::class, ['GET', 'POST'], 'cp_add_lpa');
    $app->get($prefix . '/:uuid/cp/remove-lpa/:lpa', CertificateProviderRemoveLpaHandler::class, 'cp_remove_lpa');
    $app->route($prefix . '/:uuid/cp/confirm-dob', ConfirmDobHandler::class, ['GET', 'POST'], 'cp_confirm_dob');
    $app->route($prefix . '/:uuid/cp/confirm-address', ConfirmHandler::class, ['GET', 'POST'], 'cp_confirm_address');
    $app->route($prefix . '/:uuid/cp/enter-postcode', PostcodeSearchHandler::class, ['GET', 'POST'], 'cp_enter_postcode');
    $app->route($prefix . '/:uuid/cp/select-address/:postcode', SelectHandler::class, ['GET', 'POST'], 'cp_select_address');
    $app->route($prefix . '/:uuid/cp/enter-address-manual', ManualEntryHandler::class, ['GET', 'POST'], 'cp_enter_address_manual');
    $app->get($prefix . '/:uuid/cp/identity-check-passed', CertificateProviderIdentityCheckPassedHandler::class, 'cp_identity_check_passed');

    // Vouching journey
    $app->route($prefix . '/:uuid/vouching/confirm-vouching', ConfirmVouchingHandler::class, ['GET', 'POST'], 'confirm_vouching');
    $app->route($prefix . '/:uuid/vouching/voucher-name', NameHandler::class, ['GET', 'POST'], 'voucher_name');
    $app->route($prefix . '/:uuid/vouching/voucher-dob', DateOfBirthHandler::class, ['GET', 'POST'], 'voucher_dob');
    $app->route($prefix . '/:uuid/vouching/enter-postcode', VoucherPostcodeSearchHandler::class, ['GET', 'POST'], 'voucher_enter_postcode');
    $app->route($prefix . '/:uuid/vouching/select-address/:postcode', VoucherSelectHandler::class, ['GET', 'POST'], 'voucher_select_address');
    $app->route($prefix . '/:uuid/vouching/enter-address-manual', VoucherManualEntryHandler::class, ['GET', 'POST'], 'voucher_enter_address_manual');
    $app->route($prefix . '/:uuid/vouching/confirm-donors', ConfirmDonorsHandler::class, ['GET', 'POST'], 'voucher_confirm_donors');
    $app->route($prefix . '/:uuid/vouching/add-donor', VoucherAddLpaHandler::class, ['GET', 'POST'], 'voucher_add_donor');
    $app->get($prefix . '/:uuid/vouching/remove-lpa/:lpa', VoucherRemoveLpaHandler::class, 'voucher_remove_lpa');
    $app->route($prefix . '/:uuid/vouching/identity-check-passed', VoucherIdentityCheckPassedHandler::class, ['GET', 'POST'], 'voucher_identity_check_passed');
};
// phpcs:enable

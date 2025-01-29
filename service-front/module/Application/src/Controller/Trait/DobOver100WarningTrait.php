<?php

declare(strict_types=1);

namespace Application\Controller\Trait;

trait DobOver100WarningTrait
{
    /**
     * Checks if a DOB requires a "over 100 years old" warning and handles confirmation.
     * Returns true if validated/saved, false if warning is displayed.
     */
    protected function handleDobOver100Warning(
        string $dateOfBirth,
        \Laminas\Http\Request $request,
        \Laminas\View\Model\ViewModel $view,
        callable $saveCallback
    ): bool {
        $birthDate = strtotime($dateOfBirth);
        $maxBirthDate = strtotime('-100 years', time());

        if ($birthDate < $maxBirthDate) {
            $warningAccepted = (bool) $request->getPost('dob_warning_100_accepted', false);

            if ($warningAccepted) {
                // Execute the save callback if provided
                if (is_callable($saveCallback)) {
                    call_user_func($saveCallback);
                }
                return true;
            }

            $view->setVariable('displaying_dob_100_warning', true);
            return false;
        }

        if (is_callable($saveCallback)) {
            call_user_func($saveCallback);
        }
        return true;
    }
}
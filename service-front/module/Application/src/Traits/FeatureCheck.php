<?php

declare(strict_types=1);

namespace Application\Traits;

use Laminas\View\Model\ViewModel;

trait FeatureCheck
{
    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     *
     * @return ViewModel|null
     */
    public function idVerify(): ViewModel|null
    {
        if (getenv("ID_CHECK_FLAG") === true) {
            return null;
        }
        $view = new ViewModel();
        return $view->setTemplate('application/error/403');
    }
}

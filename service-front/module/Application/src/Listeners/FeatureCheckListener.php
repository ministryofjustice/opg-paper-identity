<?php

declare(strict_types=1);

namespace Application\Listeners;

class FeatureCheckListener
{
    /**
     * @psalm-suppress DocblockTypeContradiction
     *
     * @param string $templatePath
     * @return string
     */
    public function idVerify(string $templatePath): string
    {
        if ((getenv("ID_CHECK_FLAG")) === true) {
            return $templatePath;
        }
        return 'error/feature403';
    }
}

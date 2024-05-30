<?php

declare(strict_types=1);

namespace Application\View;

use ArrayAccess;
use JsonSerializable;
use Laminas\View\Model\JsonModel as LaminasJsonModel;
use Traversable;

class JsonModel extends LaminasJsonModel
{
    /**
     * @param  array|ArrayAccess|Traversable|JsonSerializable $variables
     * @param  bool $overwrite Whether or not to overwrite the internal container with $variables
     */
    public function setVariables($variables, $overwrite = false)
    {
        if ($variables instanceof JsonSerializable) {
            $variables = $variables->jsonSerialize();
        }

        return parent::setVariables($variables, $overwrite);
    }
}

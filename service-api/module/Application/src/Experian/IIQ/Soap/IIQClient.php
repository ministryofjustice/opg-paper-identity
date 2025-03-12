<?php

declare(strict_types=1);

namespace Application\Experian\IIQ\Soap;

use SoapClient;

/**
 * @method SAA(array $request): object
 * @method RTQ(array $request): object
 */
class IIQClient extends SoapClient
{
}

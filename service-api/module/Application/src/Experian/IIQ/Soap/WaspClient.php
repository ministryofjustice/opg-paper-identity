<?php

declare(strict_types=1);

namespace Application\Experian\IIQ\Soap;

use SoapClient;

/**
 * Should not be used directly. Use WaspService instead.
 *
 * @see Application\Experian\IIQ\WaspService
 *
 * @method LoginWithCertificate(array $request): object{LoginWithCertificateResult: string}
 */
class WaspClient extends SoapClient
{
}

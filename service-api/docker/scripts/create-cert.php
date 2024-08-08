<?php

declare(strict_types=1);

use Aws\SecretsManager\SecretsManagerClient;

include '/var/www/vendor/autoload.php';

$prefix = getenv('SECRETS_MANAGER_PREFIX');
if (!is_string($prefix)) {
    throw new RuntimeException('Env var "SECRETS_MANAGER_PREFIX" required');
}

$smClient = new SecretsManagerClient([
  'endpoint' => getenv('SECRETS_MANAGER_ENDPOINT'),
]);

$certificate = $smClient->getSecretValue([
    'SecretId' => $prefix . 'experian-idiq/certificate',
]);

$certificateKey = $smClient->getSecretValue([
  'SecretId' => $prefix . 'experian-idiq/certificate-key',
]);

$pemFilename = '/opg-private/experian-iiq-cert.pem';
$pemContents = $certificate['SecretString'] . "\n\n" . $certificateKey['SecretString'];

file_put_contents($pemFilename, $pemContents);
chmod($pemFilename, 0400);

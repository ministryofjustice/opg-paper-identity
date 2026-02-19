<?php

use Laminas\ServiceManager\ServiceManager;

$config = include 'config/config.php';

$sm = new ServiceManager($config['dependencies']);

$sm->setService('config', $config);

return $sm;

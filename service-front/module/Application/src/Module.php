<?php

declare(strict_types=1);

namespace Application;

use Application\Listeners\FeatureCheckListener;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap($e)
    {
        $application = $e->getApplication();
        $config      = $application->getConfig();
        $view        = $application->getServiceManager()->get('ViewHelperManager');
        // You must have these keys in you application config
        $view->headTitle($config['view']['base_title']);

        // This is your custom listener
        $listener   = new Listeners\ViewListener();
        $listener->setView($view);
        $listener->attach($application->getEventManager());
    }
}

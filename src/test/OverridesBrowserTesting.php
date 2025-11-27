<?php

use markhuot\craftpest\browser\CraftHttpServer;

trait OverridesBrowserTesting
{
    public function bootOverridesBrowserTesting(): void
    {
        // If browser testing is not installed we can skip these overrides
        if (! class_exists(\Pest\Browser\ServerManager::class)) {
            return;
        }

        // Override Pest Browser's ServerManager HTTP server with a custom implementation
        $serverManager = \Pest\Browser\ServerManager::instance();
        $reflect = new ReflectionClass($serverManager);
        $reflect->getProperty('http')->setAccessible(true);
        $reflect->getProperty('http')->setValue($serverManager, new CraftHttpServer);
    }
}

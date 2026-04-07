<?php

namespace markhuot\craftpest\test;

use markhuot\craftpest\browser\CraftHttpServer;
use Pest\Browser\ServerManager;
use Pest\Browser\Support\Port;

trait ConfiguresBrowserTesting
{
    public function setUpConfiguresBrowserTesting(): void
    {
        // If the browser plugin is not available, we don't need to configure anything
        if (! class_exists(ServerManager::class)) {
            return;
        }

        $this->configureBrowserTesting();
    }

    /**
     * Configure browser testing to use CraftHttpServer instead of LaravelHttpServer
     */
    private function configureBrowserTesting(): void
    {
        try {
            $reflection = new \ReflectionClass(ServerManager::class);
            $instance = $reflection->getMethod('instance')->invoke(null);

            $httpProperty = $reflection->getProperty('http');
            $httpProperty->setAccessible(true);
            $httpProperty->setValue(
                $instance,
                new CraftHttpServer(
                    ServerManager::DEFAULT_HOST,
                    Port::find(),
                )
            );
        } catch (\Throwable) {
            // Silently fail if we can't configure browser testing
            // This might happen if the browser plugin isn't installed
        }
    }
}

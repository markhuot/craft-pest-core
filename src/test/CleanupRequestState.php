<?php

namespace markhuot\craftpest\test;

use Craft;
use markhuot\craftpest\web\Application;

trait CleanupRequestState
{
    /**
     * Cleanup any request state that may be left over from previous tests.
     *
     * This is normally cleaned up (and run) after a request goes through Craft's router.
     * However, if a test isn't interacting with the route and is just calling actions or
     * running queues then the after request callbacks won't be cleared.
     */
    public function tearDownCleanupRequestState(): void
    {
        $app = Craft::$app;

        if ($app instanceof Application) {
            $reflect = new \ReflectionClass($app);
            while ($reflect && ! $reflect->hasProperty('afterRequestCallbacks')) {
                $reflect = $reflect->getParentClass();
            }

            if ($reflect) {
                $property = $reflect->getProperty('afterRequestCallbacks');
                $property->setValue($app, []);
            }
        }
    }
}

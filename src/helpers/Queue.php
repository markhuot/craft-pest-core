<?php

namespace markhuot\craftpest\helpers\queue;

use Craft;

if (! function_exists('queue')) {
    function queue($callback = null): mixed
    {
        $result = null;

        if ($callback) {
            $result = $callback();
        }

        Craft::$app->queue->run();

        return $result;
    }
}

<?php

namespace markhuot\craftpest\helpers\queue;

use Craft;

if (! function_exists('queue')) {
    function queue($callback)
    {
        $result = $callback();

        Craft::$app->queue->run();

        return $result;
    }
}

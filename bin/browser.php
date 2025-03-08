<?php

use markhuot\craftpest\webdriver\Browser;

include __DIR__.'/../vendor/autoload.php';

$callstack = unserialize($argv[1]);

$constructorArgs = [];
if ($callstack[0][0] === '__construct') {
    $constructorArgs = array_slice($callstack[0], 1);
}


$browser = new Browser(...$constructorArgs);
foreach (array_slice($callstack, 1) as $call) {
    $method = $call[0];
    $args = array_slice($call, 1);
    $browser->$method(...$args);
}

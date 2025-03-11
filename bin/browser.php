<?php

use markhuot\craftpest\webdriver\Browser;

include __DIR__.'/../vendor/autoload.php';

$callstack = unserialize($argv[1]);

$constructorArgs = [];
if ($callstack[0][0] === '__construct') {
    $constructorArgs = $callstack[0][1];
}

$returnValue = null;
$browser = new Browser(...$constructorArgs);
foreach (array_slice($callstack, 1) as $call) {
    $method = $call[0];
    $args = $call[1] ?? [];
    try {
        $returnValue = $browser->$method(...$args);
    } catch (\Facebook\WebDriver\Exception\Internal\UnexpectedResponseException $e) {
        if ($method !== 'quit') {
            throw $e;
        }
    }
    $called[] = $method;
}

echo serialize([
    'sessionId' => $browser->getWebDriverSessionId(),
    'returnValue' => $returnValue,
]);

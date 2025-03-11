<?php

namespace markhuot\craftpest\helpers\http;

use markhuot\craftpest\web\TestableResponse;
use Pest\Expectation;
use Pest\PendingCalls\TestCall;

use function markhuot\craftpest\helpers\test\test;

function get(string $uri = '/'): TestableResponse|TestCall
{
    return test()->get($uri);
}

function expectGet($uri = '/'): \Pest\Expectation
{
    return new Expectation(fn () => test()->get($uri));
}

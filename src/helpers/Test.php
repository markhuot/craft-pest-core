<?php

namespace markhuot\craftpest\helpers\test;

use markhuot\craftpest\test\TestCase;
use Mockery;
use Pest\Concerns\Expectable;
use Pest\PendingCalls\TestCall;

if (!function_exists('mock')) {
    function mock($className) {
        $mock = Mockery::mock($className);
        \Craft::$container->set($className, $mock);
        return $mock;
    }
}

if (!function_exists('spy')) {
    function spy($className) {
        $spy = Mockery::spy($className);
        \Craft::$container->set($className, $spy);
        return $spy;
    }
}

/**
 * @return Expectable|TestCall|TestCase|mixed
 */
function test()
{
    return \test();
}

// The default dump() and dd() methods that ship with Craft don't play well with Pest so
// set the correct versions early
function dump(...$args)
{
    $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner;
    $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper;
    foreach ($args as $arg) {
        $dumper->dump($cloner->cloneVar($arg));
    }
}

function dd(...$args)
{
    dump(...$args);
    die;
}

expect()->extend('toMatchElementSnapshot', function () {
    $this->toSnapshot()->toMatchSnapshot(); // @phpstan-ignore-line
});

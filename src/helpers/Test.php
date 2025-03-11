<?php

namespace markhuot\craftpest\helpers\test;

use markhuot\craftpest\test\TestCase;
use Mockery;
use Pest\Concerns\Expectable;
use Pest\PendingCalls\TestCall;

function mock($className)
{
    $mock = Mockery::mock($className);
    \Craft::$container->set($className, $mock);

    return $mock;
}

function spy($className)
{
    $spy = Mockery::spy($className);
    \Craft::$container->set($className, $spy);

    return $spy;
}

/**
 * @return Expectable|TestCall|TestCase|mixed
 */
function test(): \Pest\Support\HigherOrderTapProxy|\Pest\PendingCalls\TestCall
{
    return \test();
}

// The default dump() and dd() methods that ship with Craft don't play well with Pest so
// set the correct versions early
function dump(...$args): void
{
    $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner;
    $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper;
    foreach ($args as $arg) {
        $dumper->dump($cloner->cloneVar($arg));
    }
}

function dd(...$args): void
{
    dump(...$args);
    exit;
}

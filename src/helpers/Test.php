<?php

namespace markhuot\craftpest\helpers\test;

use Mockery;

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

// The default dump() and dd() methods that ship with Craft don't play well with Pest so
// set the correct versions early
function dump($args)
{
    $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner;
    $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper;
    $dumper->dump($cloner->cloneVar($args));
}

function dd($args)
{
    dump($args);
    die;
}

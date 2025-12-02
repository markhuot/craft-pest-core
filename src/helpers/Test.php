<?php

namespace markhuot\craftpest\helpers\test;

use markhuot\craftpest\browser\VisitTemplateConfig;
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
    exit;
}

/**
 * Set the default layout for visitTemplate() calls.
 *
 * Call this in your Pest.php file to wrap all visitTemplate() renders
 * in a layout template:
 *
 * ```php
 * use function markhuot\craftpest\helpers\test\useDefaultVisitTemplateLayout;
 *
 * useDefaultVisitTemplateLayout('_layouts/base', 'content');
 * ```
 *
 * @param  string  $layout  The layout template path
 * @param  string  $block  The block name where template content will be rendered
 */
function useDefaultVisitTemplateLayout(string $layout, string $block = 'content'): void
{
    VisitTemplateConfig::setDefaultLayout($layout, $block);
}

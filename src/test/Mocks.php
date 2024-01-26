<?php

namespace markhuot\craftpest\test;

use Craft;
use Mockery\MockInterface;

trait Mocks
{
    public function tearDownMocks(): void
    {
        foreach (Craft::$container->getDefinitions() as $class => $definition) {
            if ($definition instanceof MockInterface) {
                Craft::$container->clear($class);
            }
        }
    }
}

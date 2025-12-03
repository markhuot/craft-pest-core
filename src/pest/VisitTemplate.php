<?php

namespace markhuot\craftpest\pest;

use Pest\Contracts\Plugins\Bootable;
use Pest\TestSuite;

class VisitTemplate implements Bootable
{
    public function boot(): void
    {
        TestSuite::getInstance()
            ->tests
            ->addTestCaseMethodFilter(new UsesVisitTemplateMethodFilter());
    }
}

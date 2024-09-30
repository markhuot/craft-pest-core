<?php

namespace markhuot\craftpest\console;

use PHPUnit\Framework\Assert;

class TestableResponse
{
    public function __construct(
        protected int $exitCode,
        protected string $stdout,
        protected string $stderr,
    ) {}

    public function assertSuccesful()
    {
        Assert::assertSame(0, $this->exitCode);
    }
}

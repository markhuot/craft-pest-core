<?php

namespace markhuot\craftpest\console;

use PHPUnit\Framework\Assert;

class TestableResponse
{
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
    ) {}

    public function assertSuccesful(): self
    {
        Assert::assertSame(0, $this->exitCode);

        return $this;
    }

    public function assertExitCode(int $exitCode): self
    {
        Assert::assertSame($exitCode, $this->exitCode);

        return $this;
    }

    public function assertSee(string $text): self
    {
        Assert::assertStringContainsString($text, $this->stdout.$this->stderr);

        return $this;
    }

    public function assertDontSee(string $text): self
    {
        Assert::assertStringNotContainsString($text, $this->stdout.$this->stderr);

        return $this;
    }
}

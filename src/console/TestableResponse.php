<?php

namespace markhuot\craftpest\console;

use PHPUnit\Framework\Assert;

/**
 * # Console Response Assertions
 *
 * A testable response is returned when running a console command action. This class provides a fluent interface for
 * asserting on the response.
 */
class TestableResponse
{
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
    ) {}

    /**
     * Assert that the console command exited successfully (with a zero exit code).
     *
     * ```php
     * $this->command(ConsoleController::class, 'actionName')->assertSuccessful();
     * ```
     */
    public function assertSuccesful(): self
    {
        Assert::assertSame(0, $this->exitCode);

        return $this;
    }

    /**
     * Assert that the console command failed (with a non-zero exit code).
     *
     * ```php
     * $this->command(ConsoleController::class, 'actionName')->assertFailed();
     * ```
     */
    public function assertFailed(): self
    {
        Assert::assertNotSame(0, $this->exitCode);

        return $this;
    }

    /**
     * Assert the integer exit code
     *
     * ```php
     * $this->command(ConsoleController::class, 'actionName')->assertExitCode(1337);
     * ```
     */
    public function assertExitCode(int $exitCode): self
    {
        Assert::assertSame($exitCode, $this->exitCode);

        return $this;
    }

    /**
     * Assert that the command contains the passed text in stdout or stderr
     *
     * ```php
     * $this->command(ConsoleController::class, 'actionName')->assertSee('text output');
     * ```
     */
    public function assertSee(string $text): self
    {
        Assert::assertStringContainsString($text, $this->stdout.$this->stderr);

        return $this;
    }

    /**
     * Assert that the command does not contain the passed text in stdout or stderr
     *
     * ```php
     * $this->command(ConsoleController::class, 'actionName')->assertDontSee('text output');
     * ```
     */
    public function assertDontSee(string $text): self
    {
        Assert::assertStringNotContainsString($text, $this->stdout.$this->stderr);

        return $this;
    }
}

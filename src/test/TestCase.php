<?php

namespace markhuot\craftpest\test;

use Craft;
use Illuminate\Support\Collection;
use markhuot\craftpest\actions\CallSeeders;
use markhuot\craftpest\web\TestableResponse;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use ActingAs,
        Benchmark,
        BrowserHelpers,
        CleanupRequestState,
        ConfiguresBrowserTesting,
        CookieState,
        DatabaseAssertions,
        Dd,
        ExecuteConsoleCommands,
        Mocks,
        Queues,
        RequestBuilders,
        SnapshotAssertions,
        WithExceptionHandling;

    public Collection $seedData;

    protected function setUp(): void
    {
        $this->callTraits('setUp');
    }

    protected function tearDown(): void
    {
        $this->callTraits('tearDown');
    }

    protected function callTraits($prefix)
    {
        $traits = [];

        $reflect = new \ReflectionClass($this);
        while ($reflect) {
            $traits = array_merge($traits, $reflect->getTraits());
            $reflect = $reflect->getParentClass();
        }

        foreach ($traits as $trait) {
            $method = $prefix.$trait->getShortName();
            if ($trait->hasMethod($method)) {
                $this->{$method}();
            }
        }
    }

    /**
     * @template TClass
     *
     * @param  class-string<TClass>  $class
     * @return TClass
     */
    public function factory(string $class)
    {
        return $class::factory();
    }

    public function seed(callable|string ...$seeders): self
    {
        $this->seedData = (new CallSeeders)->handle(...$seeders);

        return $this;
    }

    public function renderTemplate(...$args): TestableResponse
    {
        $content = Craft::$app->getView()->renderTemplate(...$args);

        return new \markhuot\craftpest\web\TestableResponse(['content' => $content]);
    }
}

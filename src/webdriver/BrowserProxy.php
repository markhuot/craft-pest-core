<?php

namespace markhuot\craftpest\webdriver;

use Craft;
use craft\helpers\UrlHelper;
use Pest\TestSuite;
use PHPUnit\Framework\Assert;
use React\EventLoop\Loop;
use Symfony\Component\Process\Process;

class BrowserProxy
{
    protected array $constructorArgs = [];

    protected array $callstack = [];

    protected static array $counter = [];

    protected ?string $sessionId = null;

    public function __construct(
        protected string $browser,
        array $arguments = [],
    ) {
        $this->constructorArgs = ['browser' => $browser, 'arguments' => $arguments];
    }

    public function visit(string $url): self
    {
        $site = Craft::$app->getSites()->getCurrentSite();

        $originalBaseUrl = $site->getBaseUrl();
        $site->setBaseUrl('http://127.0.0.1:8080/');
        $url = UrlHelper::siteUrl($url);
        $site->setBaseUrl($originalBaseUrl);

        $this->callstack[] = ['visit', [$url]];

        return $this;
    }

    public function screenshot(): void
    {
        [$filename, $alternateFilename] = $this->getScreenshotFilename();
        $shouldCreate = ! file_exists($filename);

        $this->callstack[] = ['screenshot', [$shouldCreate, $filename, $alternateFilename]];
        $this->send();

        if (file_exists($alternateFilename)) {
            Assert::assertFileEquals($filename, $alternateFilename, 'Screenshots do not match');
        } else {
            // @phpstan-ignore-next-line
            test()->markTestIncomplete('No screenshot found for '.basename($filename).', creating one now');
        }
    }

    public function getTitle(): ?string
    {
        $this->callstack[] = ['getTitle'];

        return $this->send();
    }

    public function getPageSource(): string
    {
        $this->callstack[] = ['getPageSource'];

        return $this->send();
    }

    public function getCurrentUrl(): string
    {
        $this->callstack[] = ['getCurrentUrl'];

        return $this->send();
    }

    public function assertSee(string $needle): void
    {
        $this->callstack[] = ['getPageSource'];
        $pageSource = $this->send();

        Assert::assertStringContainsString($needle, $pageSource);
    }

    public function quit(): void
    {
        $this->callstack[] = ['quit'];

        $this->send();
    }

    public function send(): mixed
    {
        // @phpstan-ignore-next-line
        TestSuite::getInstance()->test->bootWebDriver($this->browser);

        $testRunner = (new Process([
            'php',
            __DIR__.'/../../bin/browser.php',
            serialize([
                ['__construct', ['sessionId' => $this->sessionId, ...$this->constructorArgs]],
                ...$this->callstack,
            ]),
        ]));
        $testRunner->start();

        $returnValue = null;

        $loop = Loop::get();
        $timer = $loop->addPeriodicTimer(1, function () use ($testRunner, &$returnValue) {
            if (! $testRunner->isRunning()) {
                if ($testRunner->getExitCode() !== 0) {
                    throw new \RuntimeException($testRunner->getOutput());
                }
                $result = unserialize($testRunner->getOutput());
                $returnValue = $result['returnValue'];
                $this->sessionId ??= $result['sessionId'];
                Loop::stop();
            }
        });

        $loop->run();

        // Remove the timer so that the next time this is run we don't re-use the old process instance
        $loop->cancelTimer($timer);

        // Clear out our callstack so events are not repeated during further chaining
        $this->callstack = [];

        return $returnValue;
    }

    /**
     * @return array<int, string>
     */
    protected function getScreenshotFilename(): array
    {
        $filename = TestSuite::getInstance()->getFilename();
        $description = TestSuite::getInstance()->getDescription();
        $rootPath = TestSuite::getInstance()->rootPath;
        $testPath = TestSuite::getInstance()->testPath;

        $relativePath = str_replace(implode(DIRECTORY_SEPARATOR, [$rootPath, $testPath]), '', $filename);
        $relativePath = substr($relativePath, 0, (int) strrpos($relativePath, '.'));
        $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

        if (($count = (static::$counter[$filename.$description] ??= 0)) > 1) {
            $description .= '__'.$count;
        }

        static::$counter[$filename.$description] = $count + 1;

        return [
            sprintf('%s/%s/.craftpest/screenshots/%s/%s.png', $rootPath, $testPath, $relativePath, $description),
            sprintf('%s/%s/.craftpest/screenshots/%s/%s_staged.png', $rootPath, $testPath, $relativePath, $description),
        ];
    }
}

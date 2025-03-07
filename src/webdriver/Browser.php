<?php

namespace markhuot\craftpest\webdriver;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Pest\TestSuite;
use PHPUnit\Framework\Assert;
use React\EventLoop\Loop;

use function markhuot\craftpest\helpers\test\dd;
use function markhuot\craftpest\helpers\test\dump;

class Browser
{
    /**
     * @var array<string, int>
     */
    protected static array $counter = [];

    public function __construct(
        protected ?RemoteWebDriver $driver,
    ) {
    }

    public function visit(string $url): self
    {
        // $loop = Loop::get();
        // $loop->addTimer(1, function () use ($url) {
        //     $this->driver->get($url);
        //     Loop::stop();
        // });
        // $loop->run();

        // $fiber = new \Fiber(function () use ($url) {
        //     $this->driver->get($url);
        // });
        // $fiber->start();

        $driver = \Facebook\WebDriver\Remote\RemoteWebDriver::create('http://localhost:4444', \Facebook\WebDriver\Remote\DesiredCapabilities::safari());
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            // we are the parent
            // pcntl_wait($status); //Protect against Zombie children
        } else {
            $driver->get($url);
            // $response = (new \GuzzleHttp\Client())->get($url);
            // dump('< '.$response->getBody()->getContents().PHP_EOL);
        }

        return $this;
    }

    public function screenshot(): self
    {
        [$filename, $alternateFilename] = $this->getScreenshotFilename();

        if (file_exists($filename)) {
            $this->driver->takeScreenshot($alternateFilename);
            $this->compare($filename, $alternateFilename);
        }
        else {
            $this->driver->takeScreenshot($filename);
            test()->markTestIncomplete('No screenshot found for '.basename($filename).', creating one now');
        }

        return $this;
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

        if (($count = static::$counter[$filename.$description]) > 1) {
            $description .= '__'.$count;
        }

        static::$counter[$filename.$description] = ($count ?? 0) + 1;

        return [
            sprintf('%s/%s/.craftpest/screenshots/%s/%s.png', $rootPath, $testPath, $relativePath, $description),
            sprintf('%s/%s/.craftpest/screenshots/%s/%s_staged.png', $rootPath, $testPath, $relativePath, $description),
        ];
    }

    protected function compare($a, $b)
    {
        Assert::assertFileEquals($a, $b, 'Screenshots do not match');
    }
}

<?php

namespace markhuot\craftpest\webdriver;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Pest\TestSuite;
use PHPUnit\Framework\Assert;

class Browser
{
    /**
     * @var array<string, int>
     */
    protected static array $counter = [];

    public function __construct(
        protected RemoteWebDriver $driver,
    ) {
    }

    public function visit(string $url): self
    {
        $this->driver->get($url);

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

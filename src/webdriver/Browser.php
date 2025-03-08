<?php

namespace markhuot\craftpest\webdriver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class Browser
{
    protected RemoteWebDriver $driver;

    public function __construct(
        ?string $sessionId = null,
        string $browser = 'safari',
        array $arguments = [],
    ) {
        $capabilities = DesiredCapabilities::$browser();

        $browserClassName = ucfirst($browser);
        $optionsClassName = "\\Facebook\\WebDriver\\{$browserClassName}\\{$browserClassName}Options";
        if (class_exists($optionsClassName)) {
            $options = new $optionsClassName;
            $options->addArguments($arguments);
            $capabilities->setCapability($optionsClassName::CAPABILITY, $options);
        }

        $driverPort = match ($browser) {
            'chrome' => 4444,
            'safari' => 4445,
            default => throw new \Exception('Unknown browser driver: '.$browser),
        };

        if ($sessionId !== null && $sessionId !== '' && $sessionId !== '0') {
            $this->driver = RemoteWebDriver::createBySessionID($sessionId, 'http://localhost:'.$driverPort, null, null, true, $capabilities);
        }
        else {
            $this->driver = RemoteWebDriver::create('http://localhost:'.$driverPort, $capabilities);
        }
    }

    public function getWebDriverSessionId()
    {
        return $this->driver->getSessionID();
    }

    public function visit(string $url): void
    {
        $this->driver->get($url);
    }

    public function screenshot($shouldCreate, $filename, $alternateFilename): void
    {
        if ($shouldCreate) {
            $this->driver->takeScreenshot($filename);
        }
        else {
            $this->driver->takeScreenshot($alternateFilename);
        }
    }

    public function getTitle(): ?string
    {
        return $this->driver->getTitle();
    }

    public function getCurrentUrl(): string
    {
        return $this->driver->getCurrentURL();
    }

    public function getPageSource(): string
    {
        return $this->driver->getPageSource();
    }

    public function quit(): void
    {
        $this->driver->quit();
    }
}

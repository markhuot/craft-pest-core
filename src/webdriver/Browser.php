<?php

namespace markhuot\craftpest\webdriver;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class Browser
{
    protected RemoteWebDriver $driver;

    public function __construct(
        string $browser = 'safari',
        array $arguments = [],
    ) {
        $capabilities = DesiredCapabilities::$browser();

        $browserClassName = ucfirst($browser);
        $optionsClassName = "\\Facebook\\WebDriver\\{$browserClassName}\\{$browserClassName}Options";
        if (class_exists($optionsClassName)) {
            $options = new $optionsClassName();
            $options->addArguments($arguments);
            $capabilities->setCapability($optionsClassName::CAPABILITY, $options);
        }

        $this->driver = RemoteWebDriver::create('http://localhost:4444', $capabilities);
    }

    public function visit(string $url): self
    {
        $this->driver->get($url);

        return $this;
    }

    public function screenshot($filename, $alternateFilename): self
    {
        if (file_exists($filename)) {
            $this->driver->takeScreenshot($alternateFilename);
        }
        else {
            $this->driver->takeScreenshot($filename);
        }

        return $this;
    }
}

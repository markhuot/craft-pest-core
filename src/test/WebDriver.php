<?php

namespace markhuot\craftpest\test;

use Craft;
use markhuot\craftpest\webdriver\BrowserProxy;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

trait WebDriver
{
    protected bool $booted = false;
    protected string $browser = 'safari';
    protected string $driverPath = '/usr/bin/safaridriver';
    protected string $driverPort = '4444';
    protected array $driverArguments = [];

    public function setUpWebDriver()
    {

    }

    public function tearDownWebDriver()
    {

    }

    public function bootWebDriver()
    {
        if ($this->booted) {
            return;
        }

        $process = new Process([$this->driverPath, '--port='.$this->driverPort]);
        $process->start();

        $phpBinaryPath = (new PhpExecutableFinder())->find();
        $webServer = (new Process([
            $phpBinaryPath,
            '-S',
            '127.0.0.1:8080',
            '-t', Craft::getAlias('@webroot/web'),
            Craft::getAlias('@vendor/craftcms/cms/bootstrap/router.php'),
        ], null, [
            'CRAFTPEST_PROXY_DB' => true,
        ]));
        $webServer->start();
        //sleep(5);
    }

    public function withBrowser(string $browser, string $driverPath, string $driverPort='4444')
    {
        $this->browser = $browser;
        $this->driverPath = $driverPath;
        $this->driverPort = $driverPort;

        return $this;
    }

    /**
     * https://developer.chrome.com/blog/chrome-for-testing/
     * https://pptr.dev/browsers-api
     */
    public function withChrome($headless=true)
    {
        if ($headless) {
            $this->driverArguments[] = '--headless';
        }

        return $this->withBrowser('chrome', '/usr/local/bin/chromedriver', '4444');
    }

    public function withSafari()
    {
        return $this->withBrowser('safari', '/usr/bin/safaridriver', '4444');
    }

    public function visit(string $url): BrowserProxy
    {
        $this->bootWebDriver();

        return (new BrowserProxy($this->browser, $this->driverArguments))->visit($url);
    }
}

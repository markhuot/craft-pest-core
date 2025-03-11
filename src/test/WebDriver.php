<?php

namespace markhuot\craftpest\test;

use Craft;
use markhuot\craftpest\webdriver\Browser;
use markhuot\craftpest\webdriver\BrowserProxy;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

trait WebDriver
{
    /** @var array<string, bool> */
    protected static array $browserDriverBooted = [];

    protected static bool $webServerBooted = false;

    protected static bool $dbProxyServerBooted = false;

    /** @var array<Browser> */
    protected array $browsers = [];

    public function setUpWebDriver() {}

    public function tearDownWebDriver(): void
    {
        foreach ($this->browsers as $browser) {
            $browser->quit();
        }
    }

    public function bootWebDriver(string $browser): void
    {
        $this->startBrowserDriverProcess($browser);
        $this->startWebServerProcess();
        $this->startdbProxyServer();
    }

    protected function startBrowserDriverProcess($browser): void
    {
        if (static::$browserDriverBooted[$browser] ?? false) {
            return;
        }

        [$driverPath, $driverPort] = match ($browser) {
            'chrome' => [(getenv('CHROMEDRIVER_PATH') ?: '/usr/local/bin/chromedriver'), (getenv('CHROMEDRIVER_PORT') ?: 4444)],
            'safari' => [(getenv('SAFARIDRIVER_PATH') ?: '/usr/bin/safaridriver'), (getenv('SAFARIDRIVER_PORT') ?: 4445)],
            default => throw new \Exception('Unknown browser driver: '.$browser),
        };
        $process = new Process([$driverPath, '--port='.$driverPort]);
        $process->start();

        if ($browser === 'safari') {
            // safaridriver provides no output so we can't do anything but wait
            // a second and _hope_ it has booted...
            sleep(1);
        } else {
            $process->waitUntil(fn ($type, $buffer): bool => str_contains((string) $buffer, 'ChromeDriver was started successfully'));
        }

        static::$browserDriverBooted[$browser] = true;
    }

    protected function startWebServerProcess(): void
    {
        if (static::$webServerBooted) {
            return;
        }

        $phpBinaryPath = (new PhpExecutableFinder)->find();
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

        static::$webServerBooted = true;
    }

    protected function startDbProxyServer(): void
    {
        if (static::$dbProxyServerBooted) {
            return;
        }

        $statements = [];
        $http = new HttpServer(function (ServerRequestInterface $request) use (&$statements) {
            ['identifier' => $identifier, 'method' => $method, 'args' => $args] = json_decode($request->getBody()->getContents(), true);
            if ($method === 'prepare') {
                $statements[$identifier] = Craft::$app->getDb()->pdo->prepare(...unserialize($args));

                return Response::plaintext('');
            }
            if ($method === '__get') {
                return \React\Http\Message\Response::json([
                    'identifier' => $identifier,
                    'method' => $method,
                    'result' => serialize($statements[$identifier]->$args),
                ]);
            }

            $result = $statements[$identifier]->$method(...unserialize($args));

            return \React\Http\Message\Response::json([
                'identifier' => $identifier,
                'method' => $method,
                'result' => serialize($result),
            ]);
        });
        $socket = new SocketServer('127.0.0.1:5551');
        $http->listen($socket);

        static::$dbProxyServerBooted = true;
    }

    public function openBrowser(string $browser, array $arguments = []): BrowserProxy
    {
        return $this->browsers[] = new BrowserProxy($browser, $arguments);
    }

    /**
     * https://developer.chrome.com/blog/chrome-for-testing/
     * https://pptr.dev/browsers-api
     */
    public function withChrome($headless = true)
    {
        $arguments = [];

        if ($headless) {
            $arguments[] = '--headless';
        }

        return $this->openBrowser('chrome', $arguments);
    }

    public function withSafari()
    {
        return $this->openBrowser('safari');
    }

    public function visit(string $url): BrowserProxy
    {
        return $this->openBrowser('chrome', ['--headless'])
            ->visit($url);
    }
}

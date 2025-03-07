<?php

namespace markhuot\craftpest\test;

use _PHPStan_e52dec71a\Symfony\Component\Process\Exception\ProcessFailedException;
use Craft;
use craft\elements\Entry;
use markhuot\craftpest\webdriver\Browser;
use React\Http\Server;

use function markhuot\craftpest\helpers\test\dd;
use function markhuot\craftpest\helpers\test\dump;

/**
 * @mixin Browser
 */
trait WebDriver
{
    static $booted = false;
    protected $driver;
    protected $socket;
    protected $statements = [];

    public function setUpWebDriver()
    {

    }

    public function tearDownWebDriver()
    {
        if (static::$booted) {
            $this->socket->close();
        }
    }

    protected function boot()
    {
        if (static::$booted) {
            return;
        }

        // Start Safari Driver so we can control the browser
        $process = new \Symfony\Component\Process\Process(['/usr/bin/safaridriver', '--port=4444']);
        $process->start();

        // Start our communication server, this is how we'll communicate from our tests to
        // the web server.
        $http = new \React\Http\HttpServer(function (\Psr\Http\Message\ServerRequestInterface $request) {
            //dump($request->getParsedBody());

            return \React\Http\Message\Response::plaintext(
                "Hello World!\n"
            );
        });
        $this->socket = new \React\Socket\SocketServer('127.0.0.1:5551');
        $http->listen($this->socket);

        // Start a Craft server so we can browse pages
        // $process = new \Symfony\Component\Process\Process([
        //     '/Users/markhuot/Library/Application Support/Herd/bin/php',
        //     '-S',
        //     '127.0.0.1:8080',
        //     '-t', Craft::getAlias('@webroot/web'),
        //     Craft::getAlias('@vendor/craftcms/cms/bootstrap/router.php'),
        // ], null, [
        //     //'CRAFT_DB_OVERRIDE' => 'foo',
        // ]);
        // $process->start(function ($type, $buffer) {
        //     if (\Symfony\Component\Process\Process::ERR === $type) {
        //         dump('ERR > '.$buffer);
        //     } else {
        //         dump('OUT > '.$buffer);
        //     }
        // });
        $process = new \Symfony\Component\Process\Process(['/Users/markhuot/Library/Application Support/Herd/bin/php', '-S', '127.0.0.1:8080', __DIR__.'/../../server.php']);
        $process->start(function ($type, $buffer) {
            //echo "1> ".$buffer;
        });

        // Output some debugging of the total count of entries
        \markhuot\craftpest\factories\Entry::factory()->create();
        dump(Entry::find()->count());

        sleep(10);


        static::$booted = true;
    }

    public function __call($method, $args)
    {
        $this->boot();

        return $this->getDefaultDriver()->$method(...$args);
    }

    protected function getDefaultDriver()
    {
        return new Browser(
            null, //$this->driver ??= \Facebook\WebDriver\Remote\RemoteWebDriver::create('http://localhost:4444', \Facebook\WebDriver\Remote\DesiredCapabilities::safari())
        );
    }
}

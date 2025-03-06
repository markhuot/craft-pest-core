<?php

namespace markhuot\craftpest\test;

use _PHPStan_e52dec71a\Symfony\Component\Process\Exception\ProcessFailedException;
use craft\elements\Entry;
use markhuot\craftpest\webdriver\Browser;
use React\Http\Server;

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
        sleep(1);

        // Output some debugging of the total count of entries
        \markhuot\craftpest\factories\Entry::factory()->section('pages')->create();
        \markhuot\craftpest\helpers\test\dump(Entry::find()->count());

        // Start the webserver
        $process = new \Symfony\Component\Process\Process([
            '/Users/markhuot/Library/Application Support/Herd/bin/php',
            //'/Users/markhuot/Sites/cepf-craft/craft',
            //'serve',
            '-S',
            '127.0.0.1:8080',
            '-t', '/Users/markhuot/Sites/cepf-craft/web',
            '/Users/markhuot/Sites/cepf-craft/vendor/craftcms/cms/bootstrap/router.php',
        ], null, [
            'CRAFT_DB_OVERRIDE' => 'foo',
        ]);
        $process->start(function ($type, $buffer): void {
            if (\Symfony\Component\Process\Process::ERR === $type) {
//                \markhuot\craftpest\helpers\test\dump('ERR > '.$buffer);
            } else {
//                \markhuot\craftpest\helpers\test\dump('OUT > '.$buffer);
            }
        });
//        if (! $process->isSuccessful()) {
//            throw new \Symfony\Component\Process\Exception\ProcessFailedException($process);
//        }
        //\markhuot\craftpest\helpers\test\dd();

        // clear out any previous messages
        $file = fopen('/tmp/craftpest.sock', 'w');
        fwrite($file, '');
        fclose($file);

        // Start a two way socket so our PHP server process can communicate back to
        // this process and we can "share" a DB transaction
        $process = new \Symfony\Component\Process\Process(['tail', '-f', '/tmp/craftpest.sock']);
        $process->start(function ($type, $buffer): void {
            if (\Symfony\Component\Process\Process::ERR === $type) {
                \markhuot\craftpest\helpers\test\dump('ERR > '.$buffer);
            } else {
                $message = json_decode($buffer, true);
                if ($message['method'] === 'prepare') {
                    $this->statements[$message['identifier']] = \Craft::$app->getDb()->pdo->prepare(unserialize($message['args']));
                }
                if ($message['method'] !== 'result') {
                    $result = $this->statements[$message['identifier']]->{$message['method']}(unserialize($message['args']));
                    $file = fopen('/tmp/craftpest.sock', 'w+');
                    fwrite($file, json_encode([
                        'identifier' => $message['identifier'],
                        'method' => 'result',
                        'result' => serialize($result),
                    ])."\n\n");
                }
            }
        });

        // Debugging if we want this process to hang around a little longer so we can test.
        //sleep(1);

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
            $this->driver ??= \Facebook\WebDriver\Remote\RemoteWebDriver::create('http://localhost:4444', \Facebook\WebDriver\Remote\DesiredCapabilities::safari())
        );
    }
}

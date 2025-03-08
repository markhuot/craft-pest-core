<?php

namespace markhuot\craftpest\webdriver;

use Craft;
use Pest\TestSuite;
use PHPUnit\Framework\Assert;
use React\EventLoop\Loop;
use React\Http\Message\Response;
use Symfony\Component\Process\Process;

use function markhuot\craftpest\helpers\test\dd;
use function markhuot\craftpest\helpers\test\dump;

class BrowserProxy
{
    protected array $callstack = [];
    protected static array $counter = [];

    public function __construct(
        string $browser,
        array $arguments=[],
    ) {
        $this->callstack[] = ['__construct', $browser, $arguments];
    }

    public function visit(string $url): self
    {
        $this->callstack[] = ['visit', $url];

        return $this;
    }

    public function screenshot(): void
    {
        [$filename, $alternateFilename] = $this->getScreenshotFilename();
        $this->callstack[] = ['screenshot', $filename, $alternateFilename];

        $this->send();

        if (file_exists($alternateFilename)) {
            Assert::assertFileEquals($filename, $alternateFilename, 'Screenshots do not match');
        }
        else {
            test()->markTestIncomplete('No screenshot found for '.basename($filename).', creating one now');
        }
    }

    public function send(): void
    {
        $statements = [];
        $http = new \React\Http\HttpServer(function (\Psr\Http\Message\ServerRequestInterface $request) use (&$statements) {
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
        $socket = new \React\Socket\SocketServer('127.0.0.1:5551');
        $http->listen($socket);

        $testRunner =(new Process([
            'php',
            __DIR__.'/../../bin/browser.php',
            serialize($this->callstack),
        ]));
        $testRunner->start();

        $loop = Loop::get();
        $loop->addPeriodicTimer(1, function () use ($testRunner) {
            if (! $testRunner->isRunning()) {
                Loop::stop();
            }
        });

        $loop->run();
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

        static::$counter[$filename.$description] = ($count ?? 0) + 1;

        return [
            sprintf('%s/%s/.craftpest/screenshots/%s/%s.png', $rootPath, $testPath, $relativePath, $description),
            sprintf('%s/%s/.craftpest/screenshots/%s/%s_staged.png', $rootPath, $testPath, $relativePath, $description),
        ];
    }
}

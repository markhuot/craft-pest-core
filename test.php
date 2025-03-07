<?php

use React\Http\HttpServer;
use React\Socket\SocketServer;
use Symfony\Component\Process\Process;
use GuzzleHttp\Client;
use React\EventLoop\Loop;
use React\Http\Message\Request;

use function markhuot\craftpest\helpers\test\dump;

include __DIR__.'/vendor/autoload.php';

$loop = Loop::get();

// Start React HTTP Server to listent for SQL queries
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    //dump($request);
    return React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});

$socket = new React\Socket\SocketServer('127.0.0.1:5551');
$http->listen($socket);

// Start PHP built-in server
$process = new Process(['php', '-S', '127.0.0.1:8080', __DIR__.'/server.php']);
$process->start(function ($type, $buffer) {
    //echo "1> ".$buffer;
});
echo "php -S started".PHP_EOL;

// start a pcntl driver
function fork() {
    $pid = pcntl_fork();

    if (! $pid) {
        $response = (new \GuzzleHttp\Client())->get('http://127.0.0.1:8080');
        echo '< '.$response->getBody()->getContents().PHP_EOL;
        exit;
    }

    return $pid;
}

$loop->addTimer(1, function () {
    fork();
});

$loop->addPeriodicTimer(1.1, function () use ($process) {
    pcntl_wait($status, WNOHANG);
    dump('2 ', $status);
    if ($status === 0) {
        $process->stop();
        Loop::stop();
    }
});

$loop->run();


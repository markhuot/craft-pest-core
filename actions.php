<?php

include __DIR__.'/vendor/autoload.php';

try {
    $response = (new \GuzzleHttp\Client())->get('http://127.0.0.1:8080');
    echo $response->getBody()->getContents().PHP_EOL;
}
catch (Throwable $e) {
    echo $e->getMessage().PHP_EOL;
}

echo "all done".PHP_EOL;

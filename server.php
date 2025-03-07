<?php

use GuzzleHttp\Client;

include __DIR__.'/vendor/autoload.php';

(new Client())->post('http://127.0.0.1:5551');
echo "child process returned".PHP_EOL;

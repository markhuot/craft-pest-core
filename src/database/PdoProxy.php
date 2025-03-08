<?php

namespace markhuot\craftpest\database;

use Craft;

use function markhuot\craftpest\helpers\test\dump;

/**
 * @mixin \PDO
 */
class PdoProxy
{
    public function __construct(
        private \PDO $pdo
    ) {
    }

    public function __call($method, $args)
    {
        $result =  $this->pdo->$method(...$args);

        if ($method === 'prepare') {
            $identifier = md5(uniqid());

            Craft::createGuzzleClient()->post('http://127.0.0.1:5551', ['json' => [
                'identifier' => $identifier,
                'method' => 'prepare',
                'args' => serialize($args)
            ]]);

            $result = new PdoStatementProxy($identifier, $result);
        }

        return $result;
    }

    public function __get($name)
    {
        return $this->pdo->$name;
    }
}

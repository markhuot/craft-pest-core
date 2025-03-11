<?php

namespace markhuot\craftpest\database;

use Craft;

/**
 * @mixin \PDOStatement
 */
class PdoStatementProxy
{
    public function __construct(
        public string $identifier,
        private readonly \PDOStatement $pdoStatement,
    ) {}

    public function __call($method, $args)
    {
        $result = Craft::createGuzzleClient()->post('http://127.0.0.1:5551', ['json' => [
            'identifier' => $this->identifier,
            'method' => $method,
            'args' => serialize($args),
        ]]);
        $results = json_decode((string) $result->getBody()->getContents(), true);

        return unserialize($results['result']);
    }

    public function __get($name)
    {
        $result = Craft::createGuzzleClient()->post('http://127.0.0.1:5551', ['json' => [
            'identifier' => $this->identifier,
            'method' => '__get',
            'args' => $name,
        ]]);
        $results = json_decode((string) $result->getBody()->getContents(), true);

        return unserialize($results['result']);
    }
}

<?php

namespace markhuot\craftpest\database;

/**
 * @mixin \PDOStatement
 */
class PdoStatementProxy
{

    public function __construct(
        public string $identifier,
        private \PDOStatement $pdoStatement,
    ) {
    }

    public function __call($method, $args)
    {
        $file = fopen('/tmp/craftpest.sock', 'w+');
        fwrite($file, json_encode([
            'identifier' => $this->identifier,
            'method' => $method,
            'args' => serialize($args)
        ])."\n\n");
        fclose($file);
        return $this->pdoStatement->$method(...$args);
    }

    public function __get($name)
    {
        $file = fopen('/tmp/craftpest.sock', 'w+');
        fwrite($file, json_encode([
            'identifier' => $this->identifier,
            'method' => '__get',
            'name' => $name,
        ])."\n\n");
        fclose($file);
        return $this->pdoStatement->$name;
    }
}

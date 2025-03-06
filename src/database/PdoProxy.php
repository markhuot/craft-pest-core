<?php

namespace markhuot\craftpest\database;

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
            //\markhuot\craftpest\helpers\test\dump('prepare: '.$identifier.serialize($args));

            $file = fopen('/tmp/craftpest.sock', 'w+');
            fwrite($file, json_encode([
                'identifier' => $identifier,
                'method' => 'prepare',
                'args' => serialize($args)
            ])."\n\n");
            fclose($file);

            $result = new PdoStatementProxy($identifier, $result);
        }

        return $result;
    }

    public function __get($name)
    {
        return $this->pdo->$name;
    }
}

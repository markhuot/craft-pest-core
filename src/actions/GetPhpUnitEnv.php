<?php

namespace markhuot\craftpest\actions;

use SimpleXMLElement;

use function markhuot\craftpest\helpers\test\dd;

class GetPhpUnitEnv
{
    public function __invoke(): array
    {
        $env = [];

        // find the PHP unit config
        foreach (['phpunit.xml', 'phpunit.xml.dist'] as $file) {
            $path = CRAFT_BASE_PATH . '/' . $file;
            if (file_exists($path)) {
                $xml = simplexml_load_file($path);
                $env = array_merge($env, $this->getEnv($xml));
            }
        }

        return $env;
    }

    public function getEnv(SimpleXMLElement $doc)
    {
        $elements = $doc->xpath('/phpunit/php/env');

        return collect($elements)->mapWithKeys(fn ($element) => [
            (string) $element->attributes()['name'] => (string) $element->attributes()['value'],
        ])->toArray();
    }
}

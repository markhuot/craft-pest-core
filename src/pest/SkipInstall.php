<?php

namespace markhuot\craftpest\pest;

use Pest\Contracts\Plugins\HandlesArguments;

class SkipInstall implements HandlesArguments
{
    public static bool $skipInstall = false;

    public function handleArguments(array $originals): array
    {
        if (! in_array('--skip-install', $originals)) {
            return $originals;
        }

        self::$skipInstall = true;

        return array_values(array_filter($originals, fn ($arg) => $arg !== '--skip-install'));
    }
}

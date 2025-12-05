<?php

namespace markhuot\craftpest\pest;

use Pest\Contracts\Plugins\HandlesArguments;

class SkipInstall implements HandlesArguments
{
    private const SKIP_INSTALL_OPTION = 'skip-install';

    public static bool $skipInstall = false;

    public function handleArguments(array $originals): array
    {
        $option = '--'.self::SKIP_INSTALL_OPTION;

        if (! in_array($option, $originals)) {
            return $originals;
        }

        self::$skipInstall = true;

        return array_values(array_filter($originals, fn ($arg) => $arg !== $option));
    }
}

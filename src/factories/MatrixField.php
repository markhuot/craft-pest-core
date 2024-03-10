<?php

namespace markhuot\craftpest\factories;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

use function markhuot\craftpest\helpers\base\version_greater_than_or_equal_to;

class MatrixField extends Field
{
    public static function factory()
    {
        if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~5.0.0')) {
            return MatrixFieldEntries::factory();
        }

        if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~4.0.0')) {
            return MatrixFieldBlocks::factory();
        }

        throw new \RuntimeException('bad version');
    }
}

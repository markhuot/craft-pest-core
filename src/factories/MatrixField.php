<?php

namespace markhuot\craftpest\factories;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

class MatrixField extends Field
{
    public static function factory(): static
    {
        if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~5.0')) {
            // @phpstan-ignore-next-line
            return MatrixFieldEntries::factory();
        }

        if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~4.0')) {
            // @phpstan-ignore-next-line
            return MatrixFieldBlocks::factory();
        }

        throw new \RuntimeException('Craft Pest is not compatible with this version of Craft CMS.');
    }
}

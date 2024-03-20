<?php

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$config = [];

if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~4.0.0')) {
    $config['includes'][] = 'phpstan-craft4.neon';
}

if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~5.0.0')) {
    $config['includes'][] = 'phpstan-craft5.neon';
}

return $config;

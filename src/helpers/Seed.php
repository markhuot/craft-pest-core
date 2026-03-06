<?php

namespace markhuot\craftpest\helpers\seed;

use markhuot\craftpest\pest\InstallsCraft;

/**
 * Set a SQL file path to seed the test database with instead of
 * running the normal Craft CMS installation process.
 *
 * Call this in your `tests/Pest.php` before `uses()`:
 *
 * ```php
 * use function markhuot\craftpest\helpers\seed\seed;
 *
 * seed('@root/database/seed.sql');
 * ```
 */
function seed(string $path): void
{
    InstallsCraft::$seedPath = $path;
}

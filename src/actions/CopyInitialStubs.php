<?php

namespace markhuot\craftpest\actions;

use craft\helpers\FileHelper;

class CopyInitialStubs
{
    public function __invoke()
    {
        if (! is_dir(CRAFT_BASE_PATH.'/tests')) {
            mkdir(CRAFT_BASE_PATH.'/tests');
        }
        if (! file_exists(CRAFT_BASE_PATH.'/tests/Pest.php')) {
            copy(__DIR__.'/../../stubs/init/ExampleTest.php', CRAFT_BASE_PATH.'/tests/ExampleTest.php');
            copy(__DIR__.'/../../stubs/init/Pest.php', CRAFT_BASE_PATH.'/tests/Pest.php');
        }
        if (! file_exists(CRAFT_BASE_PATH.'/phpunit.xml')) {
            copy(__DIR__.'/../../stubs/init/phpunit.xml', CRAFT_BASE_PATH.'/phpunit.xml');
        }
        if (! is_dir(CRAFT_BASE_PATH.'/modules')) {
            FileHelper::createDirectory(CRAFT_BASE_PATH.'/modules/pest/seeders');
            copy(__DIR__.'/../../stubs/seeders/DatabaseSeeder.php', CRAFT_BASE_PATH.'/modules/pest/seeders/DatabaseSeeder.php');
        }
    }
}

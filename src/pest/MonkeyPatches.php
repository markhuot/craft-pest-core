<?php

namespace markhuot\craftpest\pest;

use Pest\Contracts\Plugins\Bootable;

use function markhuot\craftpest\helpers\test\dd;

class MonkeyPatches implements Bootable
{
    public function boot(): void
    {
        $vendorDir = dirname(\Composer\Factory::getComposerFile()) . '/vendor/';

        $entry = file_get_contents($vendorDir . '/craftcms/cms/src/elements/Entry.php');
        $entry = preg_replace('/^<\?php/', '', $entry);
        $entry = preg_replace('/(class Entry.+?\{)/s', '$1 use \\markhuot\\craftpest\\traits\\Snapshotable;', $entry);
        eval($entry);
    }
}

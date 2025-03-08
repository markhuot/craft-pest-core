<?php

namespace markhuot\craftpest\pest;

use Pest\Contracts\Plugins\Bootable;

class MonkeyPatches implements Bootable
{
    public function boot(): void
    {
        $vendorDir = dirname(\Composer\Factory::getComposerFile()).'/vendor/';

        $entry = file_get_contents($vendorDir.'/craftcms/cms/src/elements/Entry.php');
        $entry = preg_replace('/^<\?php/', '', $entry);
        $entry = preg_replace('/(class Entry.+?\{)/s', '$1 use \\markhuot\\craftpest\\traits\\Snapshotable;', (string) $entry);
        eval($entry);

        $asset = file_get_contents($vendorDir.'/craftcms/cms/src/elements/Asset.php');
        $asset = preg_replace('/^<\?php/', '', $asset);
        $asset = preg_replace('/(class Asset.+?\{)/s', '$1 use \\markhuot\\craftpest\\traits\\Snapshotable;', (string) $asset);
        eval($asset);

        if (file_exists($vendorDir.'/craftcms/cms/src/elements/MatrixBlock.php')) {
            $matrixBlock = file_get_contents($vendorDir.'/craftcms/cms/src/elements/MatrixBlock.php');
            $matrixBlock = preg_replace('/^<\?php/', '', $matrixBlock);
            $matrixBlock = preg_replace('/(class MatrixBlock.+?\{)/s', '$1 use \\markhuot\\craftpest\\traits\\Snapshotable;', (string) $matrixBlock);
            eval($matrixBlock);
        }
    }
}

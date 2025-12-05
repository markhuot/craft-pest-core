<?php

namespace markhuot\craftpest\actions;

use Composer\Script\Event;

class PatchPestBinary
{
    public static function handle(Event $event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $pestBin = $vendorDir.'/pestphp/pest/bin/pest';

        if (! file_exists($pestBin)) {
            return;
        }

        $content = file_get_contents($pestBin);

        // Check if already patched
        $patch = 'error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);';
        if (str_contains($content, $patch)) {
            return;
        }

        // Find the $localPath assignment and add error_reporting after it
        $search = "\$localPath = dirname(__DIR__).'/vendor/autoload.php';";
        $replace = "\$localPath = dirname(__DIR__).'/vendor/autoload.php';\n\n    {$patch}";

        $patched = str_replace($search, $replace, $content);

        if ($patched !== $content) {
            file_put_contents($pestBin, $patched);
            $event->getIO()->write('<info>Patched vendor/pestphp/pest/bin/pest to suppress deprecation warnings</info>');
        }
    }
}

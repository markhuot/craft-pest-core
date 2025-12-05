<?php

namespace markhuot\craftpest\actions;

class PatchPestBinary
{
    public function __invoke(): void
    {
        $pestBin = CRAFT_VENDOR_PATH.'/pestphp/pest/bin/pest';
        $pestBinBackup = $pestBin.'.backup';

        if (! file_exists($pestBin)) {
            return;
        }

        // Resolve to real path and verify it's within vendor directory
        $realPath = realpath($pestBin);
        $vendorPath = realpath(CRAFT_VENDOR_PATH);
        if ($realPath === false || $vendorPath === false || ! str_starts_with($realPath, $vendorPath.DIRECTORY_SEPARATOR)) {
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
            // Create backup before patching
            copy($pestBin, $pestBinBackup);

            file_put_contents($pestBin, $patched);

            // Verify syntax is valid using the resolved real path
            exec('php -l '.escapeshellarg($realPath).' 2>&1', $output, $exitCode);

            if ($exitCode !== 0) {
                // Syntax error - restore from backup
                unlink($pestBin);
                rename($pestBinBackup, $pestBin);

                return;
            }

            // Patch successful, remove backup
            unlink($pestBinBackup);
        }
    }
}

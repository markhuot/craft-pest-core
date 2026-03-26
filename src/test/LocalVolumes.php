<?php

namespace markhuot\craftpest\test;

use craft\awss3\Volume;
use craft\volumes\Local;

/**
 * If you're using an older version of Craft that does not support swappable filesystems you can
 * use the `LocalVolumes` trait to convert any S3 volumes in to local folder volumes during
 * test.
 *
 * Add the `LocalVolumes` trait to your `Pest.php`'s `uses()` call like so,
 *
 * ```php
 * uses(
 *   markhuot\craftpest\test\TestCase::class,
 *   markhuot\craftpest\test\LocalVolumes::class,
 * );
 * ```
 */
trait LocalVolumes
{
    public function setUpLocalVolumes()
    {
        \Craft::$container->set(Volume::class, function ($container, $params, $config) {
            if (empty($config['id'])) {
                return new Volume($config);
            }

            return new Local([
                'id' => $config['id'],
                'uid' => $config['uid'],
                'name' => $config['name'],
                'handle' => $config['handle'],
                'hasUrls' => $config['hasUrls'],
                'url' => "@web/volumes/{$config['handle']}",
                'path' => "@webroot/volumes/{$config['handle']}",
                'sortOrder' => $config['sortOrder'],
                'dateCreated' => $config['dateCreated'],
                'dateUpdated' => $config['dateUpdated'],
                'fieldLayoutId' => $config['fieldLayoutId'],
            ]);
        });
    }
}

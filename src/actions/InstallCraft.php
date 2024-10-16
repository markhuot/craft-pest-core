<?php

namespace markhuot\craftpest\actions;

use Craft;
use craft\console\Application;
use craft\console\controllers\InstallController;
use craft\helpers\App;
use craft\helpers\ArrayHelper;

class InstallCraft
{
    public function __invoke(): void
    {
        $args = [
            'username' => App::env('CRAFT_INSTALL_USERNAME') ?? 'user@example.com',
            'email' => App::env('CRAFT_INSTALL_EMAIL') ?? 'user@example.com',
            'password' => App::env('CRAFT_INSTALL_PASSWORD') ?? 'secret',
            'interactive' => App::env('CRAFT_INSTALL_INTERACTIVE') ?? '0',
        ];

        if (! file_exists(Craft::getAlias('@config/project/project.yaml'))) {
            $args = array_merge($args, [
                'siteName' => App::env('CRAFT_INSTALL_SITENAME') ?? '"Craft CMS"',
                'siteUrl' => App::env('CRAFT_INSTALL_SITEURL') ?? 'http://localhost:8080',
                'language' => App::env('CRAFT_INSTALL_LANGUAGE') ?? 'en-US',
            ]);
        }

        $getConfig = function (string $path) {
            if (file_exists($path)) {
                return require $path;
            }

            return [];
        };

        $config = ArrayHelper::merge(
            $getConfig(Craft::$app->getPath()->getVendorPath().'/craftcms/cms/src/config/app.php'),
            $getConfig(Craft::$app->getPath()->getVendorPath().'/craftcms/cms/src/config/app.console.php'),
            $getConfig(Craft::$app->getPath()->getConfigPath().'/app.php'),
            $getConfig(Craft::$app->getPath()->getConfigPath().'/app.console.php'),
            [
                'id' => 'CraftCMSConsole',
                'name' => 'Craft CMS Console',
                'components' => [
                    'config' => Craft::$app->getConfig(),
                ],
            ],
        );
        unset($config['class']);

        Craft::createObject(InstallController::class, [
            'id' => 'foo',
            'module' => new Application($config),
            'config' => $args,
        ])->runAction('craft');
    }
}

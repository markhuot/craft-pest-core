<?php

namespace markhuot\craftpest\console;

use craft\console\Controller;
use craft\helpers\FileHelper;
use markhuot\craftpest\actions\RenderCompiledClasses;
use markhuot\craftpest\Pest;
use Symfony\Component\Process\Process;
use yii\console\ExitCode;

use function markhuot\craftpest\helpers\base\version_greater_than_or_equal_to;

/**
 * # CLI Commands
 */
class PestController extends Controller
{
    public bool $force = false;

    public ?string $namespace = null;

    public function options($actionID): array
    {
        if (in_array($actionID, ['init', 'generate-mixins'], true)) {
            return [
                'force',
            ];
        }

        if (in_array($actionID, ['seed'], true)) {
            return [
                'namespace',
            ];
        }

        return [];
    }

    /**
     * Run the Pest tests with `php craft pest`. This is a convienence function that internally calls the
     * `pest/init` method and then `./vendor/bin/pest` executable.
     *
     * You may pass any pest options to this command by separating them with a `--`. For example, to filter
     * down to a specific test you may run `php craft pest -- --filter="renders the homepage"`.
     */
    public function actionIndex()
    {
        $this->runInit();
        $this->runTests();

        return ExitCode::OK;
    }

    /**
     * Running `php craft pest/init` will create the `tests` directory, an associated `tests/Pest.php` file, a
     * default `phpunit.xml` file, and a `modules/pest/seeders` directory. If any of these files or directories
     * already exist they will be skipped.
     *
     * This command id idempotent and can be run multiple times without issue. If you even want to reset your
     * setup to the default `Pest.php`, for example, you can delete your `Pest.php` and re-run `php craft pest/init`
     * to have the file recreated.
     */
    public function actionInit()
    {
        $this->runInit();

        return ExitCode::OK;
    }

    protected function runInit()
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
        if (is_dir(CRAFT_BASE_PATH.'/modules')) {
            FileHelper::createDirectory(CRAFT_BASE_PATH.'/modules/pest/seeders');
            copy(__DIR__.'/../../stubs/seeders/DatabaseSeeder.php', CRAFT_BASE_PATH.'/modules/pest/seeders/DatabaseSeeder.php');
        }
    }

    protected function runTests()
    {
        $params = $this->request->getParams();
        $pestOptions = [];
        $stdOutIndex = array_search('--', $params, true);

        if ($stdOutIndex !== false) {
            $pestOptions = array_slice($params, ++$stdOutIndex);
        }

        $process = new Process(['./vendor/bin/pest', ...$pestOptions]);
        $process->setTty(true);
        $process->setTimeout(null);
        $process->start();

        foreach ($process as $type => $data) {
            if ($type === $process::OUT) {
                echo $data;
            } else {
                echo $data;
            }
        }
    }

    public function actionCompileTemplates()
    {
        $compiledTemplatesDir = \Craft::$app->path->getCompiledTemplatesPath();
        FileHelper::removeDirectory($compiledTemplatesDir);

        $compileTemplates = function ($path, $base = '') {
            if (! is_string($path)) {
                return;
            }

            $directory = new \RecursiveDirectoryIterator($path);
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator($iterator, '/^.+\.(html|twig)$/i', \RecursiveRegexIterator::GET_MATCH);
            foreach ($regex as $match) {
                $logicalName = ltrim(substr($match[0], strlen($path)), '/');
                if ($logicalName === 'index.twig' || $logicalName === 'index.html') {
                    $logicalName = '';
                }
                $oldTemplateMode = \Craft::$app->view->getTemplateMode();
                \Craft::$app->view->setTemplateMode('site');
                $twig = \Craft::$app->view->twig;
                if (version_greater_than_or_equal_to(\Craft::$app->version, '4')) {
                    // @phpstan-ignore-next-line Ignored because one of these will fail based on the installed version of Craft
                    $twig->loadTemplate($twig->getTemplateClass($logicalName), $logicalName);
                } elseif (version_greater_than_or_equal_to(\Craft::$app->version, '3')) {
                    // @phpstan-ignore-next-line Ignored because one of these will fail based on the installed version of Craft
                    $twig->loadTemplate($logicalName);
                }
                \Craft::$app->view->setTemplateMode($oldTemplateMode);
            }
        };

        // hack
        $compileTemplates(\Craft::getAlias('@templates'));

        return 0;
    }

    public function actionGenerateMixins()
    {
        // $result = (new RenderCompiledClasses)->handle($this->force);
        $result = false;

        // @phpstan-ignore-next-line
        if ($result) {
            echo "Mixins successfully generated!\n";
        } else {
            echo "Mixins already exist, skipping.\n";
        }

        return ExitCode::OK;
    }

    /**
     * Pest comes with a built-in database seeder that can be called in your own tests or via the command
     * line. You may run the seeder with `php craft pest/seed`. By default, this will look for a class
     * called \modules\pest\seeders\DatabaseSeeder. You may override this by passing a fully qualified class
     * name as the first argument. For example, `php craft pest/seed \\modules\\pest\\seeders\\UserSeeder`.
     *
     * Seeders are __invoke-able classes. Inside the invoke method you are free to seed your database however
     * you would like, although commonly you'll use factories to create your data. For example:
     *
     * ```php
     * class DatabaseSeeder
     * {
     *     public function __invoke()
     *     {
     *         return \markhuot\craftpest\factories\Entry::factory()->count(10)->create();
     *     }
     * }
     * ```
     *
     * You can override the defaults with the following environment variables,
     *
     * ```bash
     * PEST_SEEDER_NAMESPACE="\modules\pest\seeders"
     * PEST_DEFAULT_SEEDER=DatabaseSeeder
     * ```
     */
    public function actionSeed($seeder = null): int
    {
        $namespace = $this->namespace ?? getenv('PEST_SEEDER_NAMESPACE') ?: '\\modules\\pest\\seeders';
        $namespace = '\\'.trim($namespace, '\\').'\\';

        $defaultSeeder = $seeder ?? (getenv('PEST_DEFAULT_SEEDER') ?: 'DatabaseSeeder');
        if (substr($defaultSeeder, 0, 1) === '\\') {
            $namespace = '';
        }

        $fqcn = $namespace.$defaultSeeder;
        (new $fqcn)();

        return 0;
    }
}

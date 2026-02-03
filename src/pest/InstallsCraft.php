<?php

namespace markhuot\craftpest\pest;

use Craft;
use craft\enums\CmsEdition;
use craft\helpers\App;
use craft\migrations\Install;
use craft\models\Site;
use craft\services\ProjectConfig;
use markhuot\craftpest\http\TestController;
use markhuot\craftpest\interfaces\RenderCompiledClassesInterface;
use Pest\Contracts\Plugins\HandlesArguments;
use Symfony\Component\Console\Output\OutputInterface;

class InstallsCraft implements HandlesArguments
{
    public function handleArguments(array $arguments): array
    {
        // Load phpunit.xml environment variables early to ensure they're available
        // before Craft is bootstrapped and installed. This fixes the issue where
        // HandlesArguments plugins run before PHPUnit processes phpunit.xml env vars.
        $this->loadPhpunitXmlEnvironmentVariables();

        if (! defined('CRAFT_BASE_PATH')) {
            $this->requireCraft();
        }

        if (in_array('--skip-install', $arguments)) {
            $arguments = array_values(array_filter($arguments, fn ($arg) => $arg !== '--skip-install'));
        } else {
            $this->install();
        }

        $this->renderCompiledClasses();

        return $arguments;
    }

    protected function logStart(string $message): float
    {
        try {
            $output = \Pest\Support\Container::getInstance()->get(\Symfony\Component\Console\Output\OutputInterface::class);
            if (! $output instanceof OutputInterface) {
                throw new \RuntimeException('No defined output');
            }
            $output->writeln("  <fg=gray>{$message}</>");
        } catch (\Throwable) {
            fwrite(STDOUT, $message."\n");
        }

        return microtime(true);
    }

    protected function logEnd(string $message, float $start): void
    {
        $duration = round(microtime(true) - $start, 2);
        try {
            $output = \Pest\Support\Container::getInstance()->get(\Symfony\Component\Console\Output\OutputInterface::class);
            if (! $output instanceof OutputInterface) {
                throw new \RuntimeException('No defined output');
            }
            $output->writeln("  <fg=green>{$message}</> <fg=gray>({$duration}s)</>");
        } catch (\Throwable) {
            fwrite(STDOUT, "{$message} ({$duration}s).\n");
        }
    }

    protected function install(): void
    {
        $wasInstalled = Craft::$app->getIsInstalled(true);

        if (! $wasInstalled) {
            $start = $this->logStart('Installing Craft CMS...');
            $this->craftInstall();
            $this->logEnd('Craft CMS installed', $start);

            // Set the Craft edition from project config immediately after a fresh install
            // because the project config YAML files are loaded during install
            $this->setEditionFromProjectConfig();
        }

        if (Craft::$app->getContentMigrator()->getNewMigrations()) {
            $start = $this->logStart('Running migrations...');
            $this->craftMigrateAll();
            $this->logEnd('Migrations complete', $start);
        }

        // We have to flush the data cache to make sure we're getting an accurate look at whether or not there
        // are pending changes.
        //
        // If you are using a separate test database from your dev database you may have an updated project
        // config on the dev side and have cached that the project-config is updated. Then, when you run the
        // tests you'll reach in to the same cache as the dev side and pull that the project config is unchanged
        // even though it actually _is_ changed. This ensures that there isn't any cache sharing between dev
        // and test.
        Craft::$app->getCache()->flush();
        if (Craft::$app->getProjectConfig()->areChangesPending(null, true)) {
            $start = $this->logStart('Applying project config changes...');
            $this->craftApplyProjectConfig();
            $this->logEnd('Project config applied', $start);

            // Set the Craft edition from project config after applying external changes
            $this->setEditionFromProjectConfig();
        }
    }

    protected function craftInstall()
    {
        // Check if there is a project config value for the default site name, url, and language before falling back
        // to user defined settings. This only happens in a clean test environment when you're trying to install an
        // existing site from a project config. Otherwise, the project will organically grow, the site will be created
        // via the UI and the project config will, in turn, update.
        $sites = Craft::$app->getProjectConfig()->get('sites', true);
        $primarySite = collect($sites ?? [])->first(fn ($site) => $site['primary'] === true);

        $args = [
            'username' => (App::env('CRAFT_INSTALL_USERNAME') ?? 'user@example.com'),
            'email' => (App::env('CRAFT_INSTALL_EMAIL') ?? 'user@example.com'),
            'password' => (App::env('CRAFT_INSTALL_PASSWORD') ?? 'secret'),
            'siteName' => $primarySite['name'] ?? (App::env('CRAFT_INSTALL_SITENAME') ?? '"Craft CMS"'),
            'siteUrl' => $primarySite['baseUrl'] ?? (App::env('CRAFT_INSTALL_SITEURL') ?? 'http://localhost:8080'),
            'language' => $primarySite['language'] ?? (App::env('CRAFT_INSTALL_LANGUAGE') ?? 'en-US'),
        ];

        $siteConfig = [
            'name' => $args['siteName'],
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => $args['siteUrl'],
            'language' => $args['language'],
            'primary' => true,
        ];

        $site = new Site($siteConfig);

        $migration = new Install([
            'db' => \Craft::$app->getDb(),
            'username' => $args['username'],
            'password' => $args['password'],
            'email' => $args['email'],
            'site' => $site,
        ]);

        $migrator = Craft::$app->getMigrator();
        $migrator->migrateUp($migration);

        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        // Make sure we save the project config data back to the database after installation, otherwise the config
        // will be empty in the DB and Craft will think it needs to re-apply everything on the next request.
        Craft::$app->getProjectConfig()->saveModifiedConfigData();

        // Make sure plugins get a chance to register themselves after installation. Normally this would happen
        // automatically because the install happens in one request and the subsequent request would boot Craft
        // with the plugins fully installed. But in our case we're still in the same process/request so we need
        // to give plugins a chance to register themselves like they normally would.
        $plugins = Craft::$app->getPlugins();
        $reflect = new \ReflectionClass($plugins);
        $reflect->getProperty('_pluginsLoaded')->setValue($plugins, false);
        $plugins->loadPlugins();
    }

    protected function setEditionFromProjectConfig(): void
    {
        $edition = Craft::$app->getProjectConfig()->get('system.edition');

        // If edition is not set in project config, default to 'pro' for testing
        if ($edition === null) {
            $edition = 'pro';
        }

        if (method_exists(App::class, 'editionIdByHandle')) {
            Craft::$app->setEdition(App::editionIdByHandle($edition));
        } elseif (class_exists(CmsEdition::class)) {
            Craft::$app->setEdition(CmsEdition::fromHandle($edition));
        } else {
            throw new \RuntimeException('Could not determine a [system.edition] based on the project config.');
        }
    }

    protected function craftMigrateAll()
    {
        Craft::$app->getContentMigrator()->up();
    }

    protected function craftApplyProjectConfig(): void
    {
        // applyExternalChanges is going to add event listeners automatically (because internally it makes calls to
        // ->reset() which calls ->init() which adds event listeners). Normally, this is fine because it gets called
        // at the _end_ of a request lifecycle. But in our case we're calling it early in the lifecycle and that's
        // adding duplicate listeners. So we'll remove the listeners here so they can be re-added without duplication.
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->off(ProjectConfig::EVENT_ADD_ITEM, [$projectConfig, 'handleChangeEvent']);
        $projectConfig->off(ProjectConfig::EVENT_UPDATE_ITEM, [$projectConfig, 'handleChangeEvent']);
        $projectConfig->off(ProjectConfig::EVENT_REMOVE_ITEM, [$projectConfig, 'handleChangeEvent']);

        Craft::$app->getProjectConfig()->applyExternalChanges();
    }

    protected function requireCraft()
    {
        require __DIR__.'/../bootstrap/bootstrap.php';

        // Set a bogus controller so plugins can interact with Craft:$app->controller without erroring
        Craft::$app->controller = new TestController('test-controller', Craft::$app);
    }

    /**
     * Load environment variables from phpunit.xml configuration file.
     *
     * This method reads the phpunit.xml file and manually sets environment variables
     * defined in the <php><env> section. This is necessary because Pest plugins
     * implementing HandlesArguments may be called before PHPUnit has a chance to
     * process the XML configuration and set these variables.
     *
     * Environment variables from phpunit.xml will override any existing environment
     * variables, allowing test-specific configuration (such as CRAFT_DB_DATABASE
     * for test isolation) to take precedence.
     */
    protected function loadPhpunitXmlEnvironmentVariables(): void
    {
        // Try to find phpunit.xml or phpunit.xml.dist in the current working directory
        $phpunitXmlPath = null;
        $possiblePaths = [
            getcwd().'/phpunit.xml',
            getcwd().'/phpunit.xml.dist',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $phpunitXmlPath = $path;
                break;
            }
        }

        // If no phpunit.xml found, nothing to load
        if ($phpunitXmlPath === null) {
            return;
        }

        // Parse the XML file
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($phpunitXmlPath);
        if ($xml === false) {
            libxml_clear_errors();

            return;
        }
        libxml_clear_errors();

        // Look for <php><env> elements and set them as environment variables
        if (isset($xml->php)) {
            foreach ($xml->php->children() as $element) {
                if ($element->getName() === 'env') {
                    $name = (string) $element['name'];
                    $value = (string) $element['value'];

                    // phpunit.xml env vars should override existing env vars
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    public function renderCompiledClasses()
    {
        if (! Craft::$app->isInstalled) {
            return;
        }

        Craft::$container->get(RenderCompiledClassesInterface::class)->handle();
    }
}

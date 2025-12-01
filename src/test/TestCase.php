<?php

namespace markhuot\craftpest\test;

use Craft;
use craft\enums\CmsEdition;
use craft\helpers\App;
use craft\migrations\Install;
use craft\models\Site;
use craft\services\ProjectConfig;
use Illuminate\Support\Collection;
use markhuot\craftpest\actions\CallSeeders;
use markhuot\craftpest\http\TestController;
use markhuot\craftpest\interfaces\RenderCompiledClassesInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use ActingAs,
        Benchmark,
        CleanupRequestState,
        ConfiguresBrowserTesting,
        CookieState,
        DatabaseAssertions,
        Dd,
        ExecuteConsoleCommands,
        Mocks,
        Queues,
        RequestBuilders,
        SnapshotAssertions,
        WithExceptionHandling;

    public Collection $seedData;

    protected function setUp(): void
    {
        $this->createApplication();

        $this->callTraits('setUp');

        // Have to do this after setup to make sure the app is installed first
        $this->renderCompiledClasses();
    }

    protected function tearDown(): void
    {
        $this->callTraits('tearDown');
    }

    protected function callTraits($prefix)
    {
        $traits = [];

        $reflect = new \ReflectionClass($this);
        while ($reflect) {
            $traits = array_merge($traits, $reflect->getTraits());
            $reflect = $reflect->getParentClass();
        }

        foreach ($traits as $trait) {
            $method = $prefix.$trait->getShortName();
            if ($trait->hasMethod($method)) {
                $this->{$method}();
            }
        }
    }

    public function createApplication()
    {
        if (! $this->needsRequireStatements()) {
            return Craft::$app;
        }

        $this->requireCraft();

        if (! Craft::$app->getIsInstalled(true)) {
            $this->craftInstall();
        }

        if (
            Craft::$app->getMigrator()->getNewMigrations() ||
            Craft::$app->getContentMigrator()->getNewMigrations()
        ) {
            $this->craftMigrateAll();
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
            $this->craftProjectConfigApply();
        }

        return Craft::$app;
    }

    protected function craftInstall()
    {
        $args = [
            'username' => (App::env('CRAFT_INSTALL_USERNAME') ?? 'user@example.com'),
            'email' => (App::env('CRAFT_INSTALL_EMAIL') ?? 'user@example.com'),
            'password' => (App::env('CRAFT_INSTALL_PASSWORD') ?? 'secret'),
            'siteName' => (App::env('CRAFT_INSTALL_SITENAME') ?? '"Craft CMS"'),
            'siteUrl' => (App::env('CRAFT_INSTALL_SITEURL') ?? 'http://localhost:8080'),
            'language' => (App::env('CRAFT_INSTALL_LANGUAGE') ?? 'en-US'),
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

        Craft::$app->getProjectConfig()->reset();
        Craft::$app->getProjectConfig()->applyExternalChanges();
        Craft::$app->getProjectConfig()->saveModifiedConfigData();

        $edition = Craft::$app->getProjectConfig()->get('system.edition');
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

    protected function craftProjectConfigApply()
    {
        // applyExternalChanges is going to add event listeners automatically (because internally it makes calls to
        // ->reset() which calls ->int() which adds event listeners). Normally, this is fine because it gets called
        // at the _end_ of a request lifecycle. But in our case we're calling it early in the lifecycle and that's
        // adding duplicate listeners. So we'll remove the listeners here so they can be re-added without duplication.
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->off(ProjectConfig::EVENT_ADD_ITEM, [$projectConfig, 'handleChangeEvent']);
        $projectConfig->off(ProjectConfig::EVENT_UPDATE_ITEM, [$projectConfig, 'handleChangeEvent']);
        $projectConfig->off(ProjectConfig::EVENT_REMOVE_ITEM, [$projectConfig, 'handleChangeEvent']);

        Craft::$app->getProjectConfig()->applyExternalChanges();
    }

    public function renderCompiledClasses()
    {
        if (! Craft::$app->isInstalled) {
            return;
        }

        Craft::$container->get(RenderCompiledClassesInterface::class)->handle();
    }

    protected function needsRequireStatements()
    {
        return ! defined('CRAFT_BASE_PATH');
    }

    protected function requireCraft()
    {
        require __DIR__.'/../bootstrap/bootstrap.php';

        // Set a bogus controller so plugins can interact with Craft:$app->controller without erroring
        Craft::$app->controller = new TestController('test-controller', Craft::$app);
    }

    /**
     * @template TClass
     *
     * @param  class-string<TClass>  $class
     * @return TClass
     */
    public function factory(string $class)
    {
        return $class::factory();
    }

    public function seed(callable|string ...$seeders): self
    {
        $this->seedData = (new CallSeeders)->handle(...$seeders);

        return $this;
    }

    public function renderTemplate(...$args)
    {
        $content = Craft::$app->getView()->renderTemplate(...$args);

        return new \markhuot\craftpest\web\TestableResponse(['content' => $content]);
    }
}

<?php

namespace markhuot\craftpest\test;

use Craft;
use craft\enums\CmsEdition;
use craft\helpers\App;
use craft\migrations\Install;
use craft\models\Site;
use Illuminate\Support\Collection;
use markhuot\craftpest\actions\CallSeeders;
use markhuot\craftpest\interfaces\RenderCompiledClassesInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use ActingAs,
        Benchmark,
        CleanupRequestState,
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
        Craft::$app->getProjectConfig()->flush();

        $edition = Craft::$app->getProjectConfig()->get('system.edition');
        Craft::$app->setEdition(CmsEdition::fromHandle($edition));
    }

    protected function craftMigrateAll()
    {
        Craft::$app->getContentMigrator()->up();
    }

    protected function craftProjectConfigApply()
    {
        Craft::$app->getProjectConfig()->applyExternalChanges();
    }

    public function renderCompiledClasses()
    {
        Craft::$container->get(RenderCompiledClassesInterface::class)->handle();
    }

    protected function needsRequireStatements()
    {
        return ! defined('CRAFT_BASE_PATH');
    }

    protected function requireCraft()
    {
        require __DIR__.'/../bootstrap/bootstrap.php';
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

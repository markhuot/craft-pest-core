<?php

namespace markhuot\craftpest\test;

use craft\helpers\App;
use markhuot\craftpest\actions\CallSeeders;
use markhuot\craftpest\actions\RenderCompiledClasses;
use markhuot\craftpest\console\TestableResponse;
use Symfony\Component\Process\Process;

class TestCase extends \PHPUnit\Framework\TestCase {

    use ActingAs,
        DatabaseAssertions,
        RequestBuilders,
        Benchmark,
        CookieState,
        Dd,
        WithExceptionHandling;

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
            $method = $prefix . $trait->getShortName();
            if ($trait->hasMethod($method)) {
                $this->{$method}();
            }
        }
    }

    public function createApplication()
    {
        if (! $this->needsRequireStatements()) {
            return \Craft::$app;
        }

        $this->requireCraft();

        $needsRefresh = false;

        if (! \Craft::$app->getIsInstalled(true)) {
            $this->craftInstall();
            $needsRefresh = true;
        }

        if (
            \Craft::$app->getMigrator()->getNewMigrations() ||
            \Craft::$app->getContentMigrator()->getNewMigrations()
        ) {
            $this->craftMigrateAll();
            $needsRefresh = true;
        }

        if (\Craft::$app->getProjectConfig()->areChangesPending(null, true)) {
            $this->craftProjectConfigApply();
            $needsRefresh = true;
        }

        // After installation, the Craft::$app may be out of sync because the installation happened in a sub
        // process. We need to force the $app to reload its state.
        if ($needsRefresh) {
            exit($this->reRunPest());
        }

        return \Craft::$app;
    }

    protected function craftInstall()
    {
        $args = [
            '--username=' . (App::env('CRAFT_INSTALL_USERNAME') ?? 'user@example.com'),
            '--email=' . (App::env('CRAFT_INSTALL_EMAIL') ?? 'user@example.com'),
            '--password=' . (App::env('CRAFT_INSTALL_PASSWORD') ?? 'secret'),
            '--interactive=' . (App::env('CRAFT_INSTALL_INTERACTIVE') ?? '0'),
        ];

        if (! file_exists(\Craft::getAlias('@config/project/project.yaml'))) {
            $args = array_merge($args, [
                '--siteName=' . (App::env('CRAFT_INSTALL_SITENAME') ?? '"Craft CMS"'),
                '--siteUrl=' . (App::env('CRAFT_INSTALL_SITEURL') ?? 'http://localhost:8080'),
                '--language=' . (App::env('CRAFT_INSTALL_LANGUAGE') ?? 'en-US'),
            ]);
        }

        $craftExePath = getenv('CRAFT_EXE_PATH') ?: './craft';
        $process = new Process([$craftExePath, 'install', ...$args]);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->start();

        foreach ($process as $type => $data) {
            if ($type === $process::OUT) {
                echo $data;
            } else {
                echo $data;
            }
        }

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed installing Craft');
        }
    }

    protected function craftMigrateAll()
    {
        $craftExePath = getenv('CRAFT_EXE_PATH') ?: './craft';
        $process = new Process([$craftExePath, 'migrate/all', '--interactive=0']);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->start();

        foreach ($process as $type => $data) {
            if ($type === $process::OUT) {
                echo $data;
            } else {
                echo $data;
            }
        }

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed migrating Craft');
        }
    }

    protected function craftProjectConfigApply()
    {
        $craftExePath = getenv('CRAFT_EXE_PATH') ?: './craft';
        $process = new Process([$craftExePath, 'project-config/apply', '--force']);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->start();

        foreach ($process as $type => $data) {
            if ($type === $process::OUT) {
                echo $data;
            } else {
                echo $data;
            }
        }

        if (!$process->isSuccessful()) {
            throw new \Exception('Project config apply failed');
        }
    }

    protected function reRunPest()
    {
        $process = new Process($_SERVER['argv']);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->start();

        foreach ($process as $type => $data) {
            if ($type === $process::OUT) {
                echo $data;
            } else {
                echo $data;
            }
        }

        return $process->getExitCode();
    }

    public function renderCompiledClasses()
    {
        (new RenderCompiledClasses)->handle();
    }

    protected function needsRequireStatements()
    {
        return !defined('CRAFT_BASE_PATH');
    }

    protected function requireCraft()
    {
        require __DIR__ . '/../bootstrap/bootstrap.php';
    }

    /**
     * @template TClass
     * @param class-string<TClass> $class
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

    public function console(array|string $command)
    {
        if (! is_array($command)) {
            $command = [$command];
        }

        $craft = getenv('CRAFT_EXE_PATH') ?: './craft';
        $process = new Process([$craft, ...$command]);
        $exitCode = $process->run();
        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();

        return new TestableResponse($exitCode, $stdout, $stderr);
    }

}

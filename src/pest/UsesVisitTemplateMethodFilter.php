<?php

namespace markhuot\craftpest\pest;

use Closure;
use Pest\Browser\Playwright\Playwright;
use Pest\Browser\Plugin;
use Pest\Browser\ServerManager;
use Pest\Browser\Support\BrowserTestIdentifier;
use Pest\Browser\Support\Screenshot;
use Pest\Contracts\TestCaseMethodFilter;
use Pest\Factories\TestCaseMethodFactory;
use Pest\Plugins\Only;
use Pest\Plugins\Parallel;
use Pest\Support\Backtrace;
use ReflectionException;
use ReflectionFunction;

class UsesVisitTemplateMethodFilter implements TestCaseMethodFilter
{
    public function accept(TestCaseMethodFactory $factory): bool
    {
        $usesBrowser = $this->isBrowserTest($factory->closure ?? fn (): null => null);

        if ($usesBrowser === false) {
            return true;
        }

        $usesDebugMethod = BrowserTestIdentifier::isDebugTest($factory);

        if ($usesDebugMethod) {
            Playwright::headed();

            Only::enable($factory);
        }

        $usesDebug = Playwright::shouldDebugAssertions();

        if ($usesDebug) {
            Playwright::headed();
        }

        $factory->proxies->add(
            $factory->filename,
            Backtrace::line(),
            '__markAsBrowserTest',
            [],
        );

        if (Parallel::isWorker() === false && Plugin::$booted === false) {
            Plugin::$booted = true;

            ServerManager::instance()->playwright()->start();
            Screenshot::cleanup();
        }

        return true;
    }

    protected function isBrowserTest(Closure $closure): bool
    {
        try {
            $ref = new ReflectionFunction($closure);
        } catch (ReflectionException) {
            return false;
        }

        $file = $ref->getFileName();

        if ($file === false) {
            return false;
        }

        $startLine = $ref->getStartLine();
        $endLine = $ref->getEndLine();
        $lines = file($file);

        if (is_array($lines) === false || $startLine < 1 || $endLine > count($lines)) {
            return false;
        }

        // @phpstan-ignore-next-line
        $code = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $tokens = token_get_all('<?php '.$code);
        $tokensCount = count($tokens);

        for ($i = 0; $i < $tokensCount - 1; $i++) {
            if (is_array($tokens[$i]) &&
                $tokens[$i][0] === T_STRING &&
                in_array($tokens[$i][1], ['visitTemplate'], true) &&
                $tokens[$i - 1][0] === T_OBJECT_OPERATOR) {
                return true;
            }
        }

        return false;
    }
}

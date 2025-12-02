<?php

namespace markhuot\craftpest\test;

use markhuot\craftpest\browser\TemplateRenderRegistry;
use Pest\Browser\Api\PendingAwaitablePage;

/**
 * Craft-specific browser testing helpers.
 *
 * Provides convenience methods for browser testing with Craft CMS,
 * such as rendering templates directly in the browser.
 */
trait BrowserHelpers
{
    /**
     * Whether the browser testing infrastructure has been bootstrapped.
     */
    private bool $browserTestingBootstrapped = false;

    /**
     * Visit a template in the browser with optional variables.
     *
     * This method registers the template and variables in a registry,
     * then visits a special URL that CraftHttpServer intercepts to
     * render the template.
     *
     * ```php
     * it('renders my template', function () {
     *     $page = $this->visitTemplate('_components/hero', [
     *         'title' => 'Hello World',
     *         'description' => 'Welcome to my site',
     *     ]);
     *
     *     $page->assertSee('Hello World');
     * });
     * ```
     *
     * @param  string  $template  The template path (e.g., '_components/hero' or 'pages/about')
     * @param  array<string, mixed>  $params  Variables to pass to the template
     * @return PendingAwaitablePage
     */
    public function visitTemplate(string $template, array $params = []): PendingAwaitablePage
    {
        // Ensure browser testing infrastructure is bootstrapped
        $this->bootstrapBrowserTestingIfNeeded();

        $token = TemplateRenderRegistry::register($template, $params);

        return $this->visit(TemplateRenderRegistry::URL_PREFIX.$token);
    }

    /**
     * Bootstrap the browser testing infrastructure if it hasn't been done yet.
     *
     * This is normally handled automatically by Pest's browser plugin when
     * it detects a `visit()` call in the test code. However, since `visitTemplate()`
     * uses `$this->visit()` internally, the plugin doesn't detect it as a browser test.
     */
    private function bootstrapBrowserTestingIfNeeded(): void
    {
        if ($this->browserTestingBootstrapped) {
            return;
        }

        // Check if the browser plugin's __markAsBrowserTest method exists
        if (method_exists($this, '__markAsBrowserTest')) {
            $this->__markAsBrowserTest();
        }

        $this->browserTestingBootstrapped = true;
    }
}

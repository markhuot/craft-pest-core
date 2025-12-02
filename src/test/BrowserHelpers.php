<?php

namespace markhuot\craftpest\test;

use markhuot\craftpest\browser\CraftHttpServer;
use Pest\Browser\Api\PendingAwaitablePage;

/**
 * Craft-specific browser testing helpers.
 *
 * Provides convenience methods for browser testing with Craft CMS,
 * such as rendering templates directly in the browser.
 *
 * @mixin \Pest\Browser\Browsable
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
     * This method builds a URL with the template path and variables as query
     * parameters, which CraftHttpServer intercepts to render the template.
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
     */
    public function visitTemplate(string $template, array $params = []): PendingAwaitablePage
    {
        $this->bootstrapBrowserTestingIfNeeded();

        $query = http_build_query([
            'template' => $template,
            'params' => json_encode($params),
        ]);

        return $this->visit(CraftHttpServer::TEMPLATE_RENDER_PATH.'?'.$query);
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

        if (method_exists($this, '__markAsBrowserTest')) {
            $this->__markAsBrowserTest();
        }

        $this->browserTestingBootstrapped = true;
    }
}

<?php

namespace markhuot\craftpest\test;

use markhuot\craftpest\browser\CraftHttpServer;
use markhuot\craftpest\browser\VisitTemplateConfig;
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
     * // Simple usage
     * $page = $this->visitTemplate('_components/hero', [
     *     'title' => 'Hello World',
     * ]);
     *
     * // With a layout (template is included in the specified block)
     * $page = $this->visitTemplate('_components/hero', [
     *     'title' => 'Hello World',
     * ], '_layouts/base', 'content');
     * ```
     *
     * @param  string  $template  The template path (e.g., '_components/hero' or 'pages/about')
     * @param  array<string, mixed>  $params  Variables to pass to the template
     * @param  string|null  $layout  Layout template to wrap the content (null uses default if set)
     * @param  string|null  $block  Block name in layout where template is rendered (null uses default)
     */
    public function visitTemplate(
        string $template,
        array $params = [],
        ?string $layout = null,
        ?string $block = null,
    ): PendingAwaitablePage {
        $this->bootstrapBrowserTestingIfNeeded();

        // Use provided layout/block or fall back to defaults
        $layout = $layout ?? VisitTemplateConfig::getDefaultLayout();
        $block = $block ?? VisitTemplateConfig::getDefaultBlock();

        $queryParams = [
            'template' => $template,
            'params' => json_encode($params),
        ];

        if ($layout !== null) {
            $queryParams['layout'] = $layout;
            $queryParams['block'] = $block;
        }

        return $this->visit(CraftHttpServer::TEMPLATE_RENDER_PATH.'?'.http_build_query($queryParams));
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

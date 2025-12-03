<?php

namespace markhuot\craftpest\test;

use markhuot\craftpest\browser\CraftHttpServer;
use markhuot\craftpest\browser\VisitTemplateConfig;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Support\Screenshot;

/**
 * # Browser Testing
 *
 * Craft Pest includes full support for Pest PHP's browser testing plugin, which provides
 * an elegant browser testing framework built on Playwright. You can interact with your
 * Craft site in real browsers and test your templates, components, and user interfaces.
 *
 * ## Installation
 *
 * First, install the Pest browser plugin and Playwright:
 *
 * ```bash
 * composer require pestphp/pest-plugin-browser --dev
 * npm install playwright@latest
 * npx playwright install
 * ```
 *
 * ## Basic Usage
 *
 * Use the `visit()` method to navigate to a URL and interact with the page:
 *
 * ```php
 * it('displays the homepage', function() {
 *     $page = $this->visit('/');
 *     $page->assertSee('Welcome to my site');
 * });
 * ```
 *
 * ## Craft-Specific Features
 *
 * Craft Pest adds the `visitTemplate()` method, which allows you to test Twig templates
 * directly without creating routes or entries:
 *
 * ```php
 * it('renders a component', function() {
 *     $page = $this->visitTemplate('_components/hero', [
 *         'title' => 'Hello World',
 *         'subtitle' => 'Welcome to our site',
 *     ]);
 *
 *     $page->assertSee('Hello World');
 *     $page->assertSee('Welcome to our site');
 * });
 * ```
 *
 * You can also wrap templates in a layout:
 *
 * ```php
 * it('renders in a layout', function() {
 *     $page = $this->visitTemplate('_components/hero', [
 *         'title' => 'Hello',
 *     ], layout: '_layouts/base', block: 'content');
 *
 *     $page->assertSee('Hello');
 * });
 * ```
 *
 * ### Setting a Global Layout
 *
 * If all your `visitTemplate()` calls should use the same layout, you can configure
 * it globally in your `tests/Pest.php` file or in a `beforeEach()` hook:
 *
 * ```php
 * // In tests/Pest.php
 * uses()->beforeEach(function() {
 *     $this->setDefaultVisitTemplateLayout('_layouts/base', 'content');
 * });
 *
 * // Or in a specific test file's beforeEach
 * beforeEach(function() {
 *     $this->setDefaultVisitTemplateLayout('_layouts/base', 'content');
 * });
 * ```
 *
 * With this configured, all `visitTemplate()` calls will automatically wrap templates
 * in your layout. You can still override it on a per-call basis if needed:
 *
 * ```php
 * // Uses the default layout
 * $page = $this->visitTemplate('_components/hero', ['title' => 'Hello']);
 *
 * // Override with a different layout
 * $page = $this->visitTemplate('_components/card', [], layout: '_layouts/minimal', block: 'main');
 *
 * // Skip the layout entirely
 * $page = $this->visitTemplate('_components/standalone', [], layout: null);
 * ```
 *
 * ## Common Browser Interactions
 *
 * Pest's browser testing provides many methods for interacting with pages:
 *
 * ```php
 * it('interacts with forms', function() {
 *     $page = $this->visit('/contact');
 *
 *     // Type into fields
 *     $page->type('#name', 'John Doe');
 *     $page->type('#email', 'john@example.com');
 *
 *     // Select options
 *     $page->select('#country', 'US');
 *
 *     // Check boxes
 *     $page->check('#newsletter');
 *
 *     // Click buttons
 *     $page->click('Submit');
 *
 *     // Assert results
 *     $page->assertSee('Thank you for your message');
 * });
 * ```
 *
 * ## Assertions
 *
 * Verify page content and state with assertions:
 *
 * ```php
 * it('has correct content', function() {
 *     $page = $this->visit('/about');
 *
 *     // Content assertions
 *     $page->assertSee('About Us');
 *     $page->assertDontSee('Secret Content');
 *     $page->assertSeeIn('.header', 'Navigation');
 *
 *     // Element visibility
 *     $page->assertVisible('.main-content');
 *     $page->assertMissing('.error-message');
 *
 *     // URL assertions
 *     $page->assertPathIs('/about');
 *
 *     // Form assertions
 *     $page->assertChecked('#terms');
 *     $page->assertValue('#name', 'John');
 * });
 * ```
 *
 * ## Browser & Device Configuration
 *
 * Test across different browsers and devices:
 *
 * ```bash
 * # Run tests in Firefox
 * ./vendor/bin/pest --browser firefox
 *
 * # Run tests in Safari
 * ./vendor/bin/pest --browser safari
 * ```
 *
 * Or configure in your test:
 *
 * ```php
 * it('works on mobile', function() {
 *     $page = $this->visit('/')->on()->mobile();
 *     $page->assertSee('Mobile Menu');
 * });
 *
 * it('works on iPhone', function() {
 *     $page = $this->visit('/')->on()->iPhone14Pro();
 *     $page->assertVisible('.mobile-nav');
 * });
 * ```
 *
 * ## Advanced Features
 *
 * Configure dark mode, locales, and other browser settings:
 *
 * ```php
 * it('supports dark mode', function() {
 *     $page = $this->visit('/')->inDarkMode();
 *     $page->assertVisible('.dark-theme');
 * });
 *
 * it('supports localization', function() {
 *     $page = $this->visit('/')->withLocale('fr-FR');
 *     $page->assertSee('Bonjour');
 * });
 * ```
 *
 * ## Debugging
 *
 * Debug your browser tests with these helpful methods:
 *
 * ```php
 * it('debugs a test', function() {
 *     $page = $this->visit('/');
 *
 *     // Pause execution and open the browser
 *     $page->debug();
 *
 *     // Take a screenshot
 *     $page->screenshot();
 *
 *     // Interactive debugging
 *     $page->tinker();
 * });
 * ```
 *
 * You can also run tests with a visible browser:
 *
 * ```bash
 * ./vendor/bin/pest --headed
 * ```
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
     * Set the default layout for visitTemplate() calls.
     *
     * Call this in a beforeEach() hook to wrap all visitTemplate() renders
     * in a layout template:
     *
     * ```php
     * beforeEach(fn () => $this->setDefaultVisitTemplateLayout('_layouts/base', 'content'));
     * ```
     *
     * @param  string  $layout  The layout template path
     * @param  string  $block  The block name where template content will be rendered
     * @return $this
     */
    public function setDefaultVisitTemplateLayout(string $layout, string $block = 'content'): static
    {
        VisitTemplateConfig::setDefaultLayout($layout, $block);

        return $this;
    }

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
     * ], layout: '_layouts/base', block: 'content');
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
        // Use provided layout/block or fall back to defaults
        $layout ??= VisitTemplateConfig::getDefaultLayout();
        $block ??= VisitTemplateConfig::getDefaultBlock();

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
}

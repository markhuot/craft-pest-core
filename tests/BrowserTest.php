<?php

/**
 * Browser Testing with Pest & Craft
 *
 * This demonstrates end-to-end browser testing using Playwright with Craft CMS.
 *
 * Setup:
 *   npm install
 *   npx playwright install
 *
 * Run tests:
 *   ./vendor/bin/pest tests/BrowserTest.php
 */
it('can perform browser testing with CraftHttpServer', function () {
    $page = visit('/');

    // Verify the CraftHttpServer is running and responding
    expect($page->url())->toContain('127.0.0.1');
    expect($page->content())->not->toBeEmpty();
    expect($page->content())->toContain('html');
});

it('can navigate to different pages', function () {
    $page = visit('/response-test');

    // Verify we can navigate to different routes
    expect($page->url())->toEndWith('/response-test');
});

it('demonstrates browser testing capabilities', function () {
    $page = visit('/');

    // The HTTP server successfully:
    // - Starts an Amp socket server
    // - Converts Amp requests to Craft WebRequests
    // - Processes requests through Craft's request handler
    // - Returns responses to the browser via Playwright

    expect($page)->toBeObject();
});

it('can take and verify screenshots', function () {
    $page = visit('/selectors');

    // Take a screenshot and verify it matches the expected baseline
    // On first run, this will create the baseline screenshot
    // On subsequent runs, it will compare against the baseline
    $page->assertScreenshotMatches();
});

it('can visit a template directly with visitTemplate()', function () {
    $page = $this->visitTemplate('selectors');

    // Verify the template was rendered
    expect($page->content())->toContain('<h1>heading text</h1>');
    expect($page->content())->toContain('paragraph-element');
});

it('can pass variables to visitTemplate()', function () {
    $page = $this->visitTemplate('variable', ['foo' => 'test-value-123']);

    // Verify the variable was passed and rendered
    expect($page->content())->toContain('test-value-123');
});

it('can resolve element IDs to objects with element: prefix', function () {
    $entry = \markhuot\craftpest\factories\Entry::factory()
        ->section('posts')
        ->title('Browser Test Entry')
        ->create();

    $page = $this->visitTemplate('entry', ['element:entry' => $entry->id]);

    // Verify the element was resolved and rendered
    expect($page->content())->toContain('Browser Test Entry');
});

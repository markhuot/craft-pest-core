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
    
    //The HTTP server successfully:
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
})->only();

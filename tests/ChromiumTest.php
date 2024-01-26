<?php

use HeadlessChromium\BrowserFactory;

it('loads chromium', function () {
    $this->useChromium()
        ->get('https://example.com');

//    $browserFactory = new BrowserFactory();
//    $browser = $browserFactory->createBrowser();
//    $page = $browser->createPage();
//    $page->navigate('https://example.com')->waitForNavigation();
//    \markhuot\craftpest\helpers\test\dd($page->evaluate('2+2')->getReturnValue());
});

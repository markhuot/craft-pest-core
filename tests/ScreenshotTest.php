<?php

use markhuot\craftpest\factories\Entry;

it('takes screenshots in Safari')
    ->withSafari()
    ->visit('/entry-count')
    ->screenshot();

it('takes screenshots in Chrome')
    ->withChrome()
    ->visit('/entry-count')
    ->screenshot();

it('communicates with the browser', function () {
    $this->withChrome()
        ->visit('/entry-count');
});

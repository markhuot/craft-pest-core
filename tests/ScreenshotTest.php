<?php

// it('takes screenshots')
//     ->visit('http://127.0.0.1:8080/')
//     ->screenshot();

it('takes screenshots')
    ->browse(function ($browser) {
        $browser->visit('http://127.0.0.1:8080')
            ->screenshot();
    });

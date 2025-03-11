<?php

use markhuot\craftpest\factories\Entry;
use markhuot\craftpest\factories\Section;
use markhuot\craftpest\factories\User;

it('takes screenshots in Safari', function () {
    $this->withSafari()
        ->visit('/entry-count')
        ->screenshot();
})->skip(fn () => getenv('CI'));

it('takes screenshots in Chrome')
    ->withChrome()
    ->visit('/entry-count')
    ->screenshot();

it('opens chrome in the foreground')
    ->withChrome(headless: false)
    ->visit('/index')
    ->assertSee('Hello World!');

it('communicates with the browser', function () {
    $browser = $this->visit('/entry-count');
    $url = $browser->getCurrentUrl();

    expect($url)->toBe('http://127.0.0.1:8080/entry-count');
});

it('shares PDO state', function () {
    Section::factory()
        ->name('Pages')
        ->uriFormat('pages/{slug}')
        ->template('entry')
        ->create();

    Entry::factory()
        ->section('pages')
        ->title('foo bar')
        ->create();

    $source = $this->visit('pages/foo-bar')
        ->getPageSource();

    expect($source)->toBe('<html><head></head><body><h1>foo bar</h1></body></html>');
});

it('can access the current user', function () {
    $user = User::factory()
        ->admin(true)
        ->create();

    $this->actingAs()
        ->visit('/current-user')
        ->assertSee($user->id);
});

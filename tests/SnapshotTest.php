<?php

use markhuot\craftpest\factories\Entry;
use markhuot\craftpest\factories\Section;

it('asserts html snapshots')
    ->get('/selectors')
    ->assertOk()
    ->assertMatchesSnapshot();

it('expects html snapshots', function () {
    $response = $this->get('/selectors')->assertOk();

    expect($response)->toMatchSnapshot();
});

it('asserts dom snapshots')
    ->get('/selectors')
    ->assertOk()
    ->querySelector('ul')
    ->assertMatchesSnapshot();

it('expects dom snapshots', function () {
    $dom = $this->get('/selectors')->assertOk()
        ->querySelector('ul');

    expect($dom)->assertMatchesSnapshot();
});

it('asserts view snapshots')
    ->renderTemplate('selectors')
    ->assertMatchesSnapshot();

it('asserts view dom snapshots')
    ->renderTemplate('selectors')
    ->querySelector('h1')
    ->assertMatchesSnapshot();

it('renders views with variables')
    ->renderTemplate('variable', ['foo' => 'bar'])
    ->assertMatchesSnapshot();

it('matches entry snapshots', function () {
    $entry = Entry::factory()
        ->section('posts')
        ->title('foo bar')
        ->create();

    expect($entry)->toMatchElementSnapshot();
});

it('matches nested entry snapshots', function () {
    $child = Entry::factory()
        ->section('posts')
        ->title('child');

    $parent = Entry::factory()
        ->section('posts')
        ->title('foo bar')
        ->entriesField([$child])
        ->create();

    $entry = \craft\elements\Entry::find()->id($parent->id)->with(['entriesField'])->one();

    expect($entry)->toMatchElementSnapshot();
});

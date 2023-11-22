<?php

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

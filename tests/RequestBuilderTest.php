<?php

it('posts to an action', function () {
    $this->post('/post-data', ['foo' => 'bar'])
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertSee('"foo":"bar"')
        ->assertOk();
});

it('posts json to an action', function () {
    $response = $this->postJson('/post-data', ['foo' => 'bar'])
        ->assertHeader('content-type', 'application/json')
        ->assertOk();

    expect($response->json()->json())->foo->toBe('bar');
});

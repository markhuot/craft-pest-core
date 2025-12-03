<?php

use function markhuot\craftpest\helpers\http\get;

it('finds selectors', function () {
    get('/selectors')
        ->querySelector('h1')->expect()
        ->text->toBe('heading text');
});

it('expects selectors')
    ->get('/selectors')
    ->expectSelector('h1')
    ->text->toBe('heading text');

it('gets inner html by class name', function () {
    get('/selectors')
        ->querySelector('.paragraph-element')->expect()
        ->innerHTML->toBe('inner html');
});

it('throws on unexpected property', function () {
    $this->expectException(\Exception::class);
    get('/selectors')
        ->querySelector('.paragraph-element')->expect()
        ->foo;
});

it('selects multiple matching nodes via expectation API', function () {
    get('/selectors')
        ->querySelector('#first ul li')->expect()
        ->count->toBe(3)
        ->text->toBe(['one', 'two', 'three']);
});

it('selects multiple matching nodes via assertion API', function () {
    get('/selectors')
        ->querySelector('#first ul li')
        ->assertCount(3)
        ->assertText(['one', 'two', 'three']);
});

it('asserts containing string', function () {
    get('/selectors')
        ->querySelector('h1')
        ->assertContainsString('heading text');
});

it('queries a nodelist', function () {
    get('/selectors')
        ->querySelector('#first')
        ->querySelector('li')
        ->assertCount(3)
        ->assertText(['one', 'two', 'three']);
});

it('clicks links')
    ->get('/links')
    ->querySelector('a')
    ->click()
    ->assertOk()
    ->assertSee('Hello World');

it('asserts attributes')
    ->get('/selectors')
    ->assertOk()
    ->querySelector('#first')
    ->assertAttribute('id', 'first');

it('gets text content as flattened string for single node', function () {
    $textContent = get('/selectors')
        ->querySelector('h1')
        ->getTextContent();

    expect($textContent)->toBe('heading text');
});

it('gets text content as flattened string for multiple nodes', function () {
    $textContent = get('/selectors')
        ->querySelector('#first ul li')
        ->getTextContent();

    expect($textContent)->toBe('onetwothree');
});

it('asserts see on single node', function () {
    get('/selectors')
        ->querySelector('h1')
        ->assertSee('heading');
});

it('asserts see on multiple nodes flattened', function () {
    get('/selectors')
        ->querySelector('#first ul li')
        ->assertSee('two');
});

it('asserts contains string on multiple nodes', function () {
    get('/selectors')
        ->querySelector('#first ul li')
        ->assertContainsString('two');
});

it('asserts text content on single node', function () {
    get('/selectors')
        ->querySelector('h1')
        ->assertTextContent('heading text');
});

it('asserts text content on multiple nodes as flattened string', function () {
    get('/selectors')
        ->querySelector('#first ul li')
        ->assertTextContent('onetwothree');
});

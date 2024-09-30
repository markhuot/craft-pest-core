<?php

test('sets request state', function () {
    Craft::$app->onAfterRequest(fn () => throw new Exception('An after request callback was not cleared.'));

    expect(true)->toBeTrue();
});

test('expects request state to be empty', function () {
    $app = Craft::$app;
    $reflect = new ReflectionClass($app);
    while ($reflect && ! $reflect->hasProperty('afterRequestCallbacks')) {
        $reflect = $reflect->getParentClass();
    }

    expect($reflect->getProperty('afterRequestCallbacks')->getValue($app))->toBeEmpty();
})->depends('sets request state');

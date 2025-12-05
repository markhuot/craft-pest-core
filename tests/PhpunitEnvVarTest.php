<?php

test('phpunit.xml env vars are accessible in tests', function () {
    // This should be set from phpunit.xml
    expect(getenv('TEST_PHPUNIT_ENV_VAR'))->toBe('test_value_from_phpunit');
    expect($_ENV['TEST_PHPUNIT_ENV_VAR'] ?? null)->toBe('test_value_from_phpunit');
});

test('craft db database env var from phpunit.xml is accessible', function () {
    // This should be set from phpunit.xml
    expect(getenv('CRAFT_DB_DATABASE'))->toBe('craftpest_test_from_phpunit');
});

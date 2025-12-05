<?php

use markhuot\craftpest\pest\InstallsCraft;

/**
 * Test to verify that InstallsCraft correctly loads environment variables
 * from phpunit.xml configuration file.
 *
 * This test ensures that the bug is fixed where InstallsCraft would not
 * pick up environment variable overrides from phpunit.xml because the
 * HandlesArguments plugin was running before PHPUnit processed the XML.
 */
test('InstallsCraft loads phpunit.xml environment variables', function () {
    // Create a temporary phpunit.xml file for testing
    $tempDir = sys_get_temp_dir().'/craft-pest-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $originalCwd = getcwd();

    // Create a test phpunit.xml with environment variables
    $phpunitXmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <php>
        <env name="TEST_VAR_FROM_PHPUNIT" value="test_value_123" />
        <env name="CRAFT_DB_DATABASE" value="test_database_name" />
    </php>
</phpunit>
XML;

    file_put_contents($tempDir.'/phpunit.xml', $phpunitXmlContent);

    // Change to temp directory so InstallsCraft can find the phpunit.xml
    chdir($tempDir);

    try {
        // Clear any existing values
        putenv('TEST_VAR_FROM_PHPUNIT');
        putenv('CRAFT_DB_DATABASE');
        unset($_ENV['TEST_VAR_FROM_PHPUNIT']);
        unset($_ENV['CRAFT_DB_DATABASE']);
        unset($_SERVER['TEST_VAR_FROM_PHPUNIT']);
        unset($_SERVER['CRAFT_DB_DATABASE']);

        // Create InstallsCraft instance and use reflection to call the protected method
        $installsCraft = new InstallsCraft();
        $reflection = new ReflectionClass($installsCraft);
        $method = $reflection->getMethod('loadPhpunitXmlEnvironmentVariables');
        $method->setAccessible(true);
        $method->invoke($installsCraft);

        // Verify the environment variables were loaded
        expect(getenv('TEST_VAR_FROM_PHPUNIT'))->toBe('test_value_123');
        expect($_ENV['TEST_VAR_FROM_PHPUNIT'] ?? null)->toBe('test_value_123');
        expect($_SERVER['TEST_VAR_FROM_PHPUNIT'] ?? null)->toBe('test_value_123');

        expect(getenv('CRAFT_DB_DATABASE'))->toBe('test_database_name');
        expect($_ENV['CRAFT_DB_DATABASE'] ?? null)->toBe('test_database_name');
        expect($_SERVER['CRAFT_DB_DATABASE'] ?? null)->toBe('test_database_name');
    } finally {
        // Restore original working directory
        chdir($originalCwd);

        // Clean up
        unlink($tempDir.'/phpunit.xml');
        rmdir($tempDir);
    }
});

test('InstallsCraft respects existing environment variables over phpunit.xml', function () {
    // This test ensures that actual environment variables take precedence
    // over phpunit.xml values (as they should)

    $tempDir = sys_get_temp_dir().'/craft-pest-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $originalCwd = getcwd();

    $phpunitXmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <php>
        <env name="TEST_PRECEDENCE_VAR" value="from_phpunit_xml" />
    </php>
</phpunit>
XML;

    file_put_contents($tempDir.'/phpunit.xml', $phpunitXmlContent);
    chdir($tempDir);

    try {
        // Set an actual environment variable
        putenv('TEST_PRECEDENCE_VAR=from_actual_env');
        $_ENV['TEST_PRECEDENCE_VAR'] = 'from_actual_env';
        $_SERVER['TEST_PRECEDENCE_VAR'] = 'from_actual_env';

        // Load phpunit.xml vars
        $installsCraft = new InstallsCraft();
        $reflection = new ReflectionClass($installsCraft);
        $method = $reflection->getMethod('loadPhpunitXmlEnvironmentVariables');
        $method->setAccessible(true);
        $method->invoke($installsCraft);

        // Verify the actual env var was NOT overwritten
        expect(getenv('TEST_PRECEDENCE_VAR'))->toBe('from_actual_env');
    } finally {
        chdir($originalCwd);
        unlink($tempDir.'/phpunit.xml');
        rmdir($tempDir);
        putenv('TEST_PRECEDENCE_VAR=');
        unset($_ENV['TEST_PRECEDENCE_VAR']);
        unset($_SERVER['TEST_PRECEDENCE_VAR']);
    }
});

test('InstallsCraft handles missing phpunit.xml gracefully', function () {
    // Test that the method doesn't fail when phpunit.xml doesn't exist

    $tempDir = sys_get_temp_dir().'/craft-pest-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $originalCwd = getcwd();
    chdir($tempDir);

    try {
        // No phpunit.xml file exists in this directory
        $installsCraft = new InstallsCraft();
        $reflection = new ReflectionClass($installsCraft);
        $method = $reflection->getMethod('loadPhpunitXmlEnvironmentVariables');
        $method->setAccessible(true);

        // Should not throw an exception
        $method->invoke($installsCraft);

        // Test passes if no exception was thrown
        expect(true)->toBeTrue();
    } finally {
        chdir($originalCwd);
        rmdir($tempDir);
    }
});

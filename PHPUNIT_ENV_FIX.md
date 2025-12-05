# Fix for phpunit.xml Environment Variable Loading in InstallsCraft

## Problem Summary

The `InstallsCraft` plugin was not picking up environment variable overrides set in `phpunit.xml`. This meant that developers could not configure test-specific database names or other environment settings through their `phpunit.xml` configuration file.

## Root Cause

### Execution Order Issue

The issue stemmed from the order in which components are initialized:

1. **Composer autoload** (`vendor/autoload.php`) is loaded first (via `bootstrap` attribute in phpunit.xml)
2. **Pest plugins** are discovered and initialized during autoload (via `composer.json` `extra.pest.plugins`)
3. **InstallsCraft::handleArguments()** is called as part of Pest's plugin system (implements `HandlesArguments`)
4. **PHPUnit processes phpunit.xml** and sets `<env>` variables *after* plugins are initialized
5. **Tests run** with environment variables now available

The problem is that steps 2-3 happen *before* step 4. When `InstallsCraft::handleArguments()` runs and attempts to install Craft CMS, it uses `App::env()` to read configuration like `CRAFT_DB_DATABASE`, `CRAFT_INSTALL_USERNAME`, etc. However, these environment variables from `phpunit.xml` haven't been set yet by PHPUnit.

### Code Path

```php
// InstallsCraft::handleArguments() is called early
public function handleArguments(array $originals): array
{
    if (! defined('CRAFT_BASE_PATH')) {
        $this->requireCraft();  // Bootstraps Craft
    }
    
    $this->install();  // Tries to use App::env() here
    // But phpunit.xml env vars aren't loaded yet!
    return $originals;
}

// Later in craftInstall()
protected function craftInstall()
{
    $args = [
        'username' => (App::env('CRAFT_INSTALL_USERNAME') ?? 'user@example.com'),
        'email' => (App::env('CRAFT_INSTALL_EMAIL') ?? 'user@example.com'),
        // These values from phpunit.xml aren't available yet
    ];
}
```

## Solution

### Implementation

The fix manually loads environment variables from `phpunit.xml` early in the `handleArguments()` method, before any Craft bootstrapping or installation occurs.

A new protected method `loadPhpunitXmlEnvironmentVariables()` was added that:

1. Searches for `phpunit.xml` or `phpunit.xml.dist` in the current working directory
2. Parses the XML file using `simplexml_load_file()`
3. Extracts all `<php><env>` elements
4. Sets them as environment variables using `putenv()`, `$_ENV`, and `$_SERVER`
5. Respects existing environment variables (doesn't override actual env vars)

### Code Changes

**src/pest/InstallsCraft.php:**

```php
public function handleArguments(array $originals): array
{
    // NEW: Load phpunit.xml env vars first
    $this->loadPhpunitXmlEnvironmentVariables();
    
    if (! defined('CRAFT_BASE_PATH')) {
        $this->requireCraft();
    }
    
    // ... rest of method
}

protected function loadPhpunitXmlEnvironmentVariables(): void
{
    // Find phpunit.xml
    $possiblePaths = [
        getcwd().'/phpunit.xml',
        getcwd().'/phpunit.xml.dist',
    ];
    
    // Parse and set <php><env> variables
    // (See implementation for full details)
}
```

## Benefits

1. **Test Isolation**: Developers can now use `phpunit.xml` to specify test-specific database names:
   ```xml
   <php>
       <env name="CRAFT_DB_DATABASE" value="craftcms_test" />
   </php>
   ```

2. **Configuration Flexibility**: All Craft environment variables can be overridden for testing:
   ```xml
   <php>
       <env name="CRAFT_DB_DATABASE" value="craftcms_test" />
       <env name="CRAFT_INSTALL_USERNAME" value="testuser@example.com" />
       <env name="CRAFT_INSTALL_SITENAME" value="Test Site" />
   </php>
   ```

3. **Environment Precedence**: Actual environment variables still take precedence over `phpunit.xml` values, maintaining expected behavior.

4. **No Breaking Changes**: The fix is backward compatible. Projects without `<php><env>` tags in `phpunit.xml` continue to work as before.

## Testing

New tests were added to verify the fix:

- **tests/InstallsCraftEnvTest.php**: Unit tests for `loadPhpunitXmlEnvironmentVariables()` method
  - Verifies environment variables are loaded from phpunit.xml
  - Ensures existing env vars take precedence
  - Confirms graceful handling of missing phpunit.xml
  
- **tests/PhpunitEnvVarTest.php**: Integration tests
  - Verifies test environment has access to phpunit.xml env vars
  - Confirms Craft-specific variables are accessible

## Usage Example

In your project's `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Test Suite">
            <directory>./tests</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <!-- Use a separate test database to avoid affecting development data -->
        <env name="CRAFT_DB_DATABASE" value="craftcms_test" />
        
        <!-- Optional: Override other Craft settings for tests -->
        <env name="CRAFT_INSTALL_USERNAME" value="testuser@example.com" />
        <env name="CRAFT_INSTALL_EMAIL" value="testuser@example.com" />
        <env name="CRAFT_QUEUE_DRIVER" value="sync" />
    </php>
</phpunit>
```

Now when tests run, InstallsCraft will use `craftcms_test` as the database name instead of the development database.

## Related Files

- `src/pest/InstallsCraft.php` - Main fix implementation
- `tests/InstallsCraftEnvTest.php` - Unit tests for the fix
- `tests/PhpunitEnvVarTest.php` - Integration tests
- `phpunit.xml` - Example configuration with env vars

## References

- [PHPUnit Configuration Documentation](https://docs.phpunit.de/en/10.5/configuration.html#the-php-element)
- [Pest Plugin System](https://pestphp.com/docs/plugins)
- [Craft CMS App::env() Documentation](https://craftcms.com/docs/4.x/config/#environmental-configuration)

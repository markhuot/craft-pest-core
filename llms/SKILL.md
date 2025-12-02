---
name: Pest Testing Framework
description: Integration with the Pest PHP testing framework for writing and running tests
---

- Running tests: `php ./vendor/bin/pest`
- Running specific tests: `php ./vendor/bin/pest tests/MyTest.php`

## Overview

Pest is an elegant PHP testing framework with a focus on simplicity. It's built on top of PHPUnit but provides a more expressive and developer-friendly syntax.

## Running Tests

### Basic Commands

```bash
# Run all tests
php ./vendor/bin/pest

# Run tests in a specific directory
php ./vendor/bin/pest tests/Unit

# Run a specific test file
php ./vendor/bin/pest tests/HeroComponentsTest.php

# Run tests with coverage
php ./vendor/bin/pest --coverage

# Run tests with coverage and minimum threshold
php ./vendor/bin/pest --coverage --min=80

# Run tests in parallel (faster execution)
php ./vendor/bin/pest --parallel

# Run tests with verbose output
php ./vendor/bin/pest -v

# Run tests and stop on first failure
php ./vendor/bin/pest --stop-on-failure

# Run tests matching a filter
php ./vendor/bin/pest --filter="HeroComponents"
```

## Test Structure

### Basic Test File

```php
<?php

// tests/ExampleTest.php

test('example test', function () {
    expect(true)->toBeTrue();
});

it('can perform assertions', function () {
    $result = 2 + 2;
    expect($result)->toBe(4);
});
```

### Organized Tests with describe()

```php
<?php

describe('HeroComponents', function () {
    it('renders correctly', function () {
        // Test implementation
    });

    it('handles empty state', function () {
        // Test implementation
    });
});
```

### Using beforeEach() and afterEach()

```php
<?php

beforeEach(function () {
    // Setup code that runs before each test
    $this->user = createUser();
});

afterEach(function () {
    // Cleanup code that runs after each test
    $this->user = null;
});

test('user can login', function () {
    expect($this->user)->not->toBeNull();
});
```

## Common Expectations

### Value Assertions

```php
expect($value)->toBe($expected);           // Strict equality (===)
expect($value)->toEqual($expected);        // Loose equality (==)
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeNull();
expect($value)->toBeEmpty();
expect($value)->toBeGreaterThan(5);
expect($value)->toBeLessThan(10);
```

### Type Assertions

```php
expect($value)->toBeInt();
expect($value)->toBeString();
expect($value)->toBeArray();
expect($value)->toBeObject();
expect($value)->toBeInstanceOf(MyClass::class);
```

### Array/Collection Assertions

```php
expect($array)->toHaveCount(3);
expect($array)->toContain('value');
expect($array)->toHaveKey('key');
expect($array)->each->toBeString();
```

### String Assertions

```php
expect($string)->toStartWith('Hello');
expect($string)->toEndWith('World');
expect($string)->toContain('test');
expect($string)->toMatch('/regex/');
```

### Negation

```php
expect($value)->not->toBe('wrong');
expect($array)->not->toBeEmpty();
```

## Datasets

Use datasets to run the same test with different inputs:

```php
<?php

it('can add numbers', function (int $a, int $b, int $expected) {
    expect($a + $b)->toBe($expected);
})->with([
    [1, 2, 3],
    [5, 5, 10],
    [10, 20, 30],
]);

// Named datasets
it('validates email', function (string $email) {
    expect(isValidEmail($email))->toBeTrue();
})->with([
    'valid email' => 'test@example.com',
    'another valid' => 'user@domain.co.uk',
]);
```

## Factories

Factories in Craft Pest provide a powerful way to create test data for your Craft CMS projects. They allow you to quickly generate entries, fields, sections, assets, and other elements with realistic test data.

### Basic Entry Creation

The simplest way to create an entry is using the `Entry::factory()` method:

```php
use markhuot\craftpest\factories\Entry;

// Create a basic entry
$entry = Entry::factory()->create();

// Create entry in a specific section
$entry = Entry::factory()
    ->section('posts')
    ->create();
```

#### Make vs Create

- `make()` - Creates instances without saving to the database
- `create()` - Creates instances and saves them to the database

```php
// Create without saving
$entry = Entry::factory()->make();

// Create and save
$entry = Entry::factory()->create();
```

#### Creating Multiple Entries

```php
// Create 5 entries
$entries = Entry::factory()
    ->section('posts')
    ->count(5)
    ->create();  // Returns a Collection when count > 1
```

### Setting Basic Entry Properties

```php
$entry = Entry::factory()
    ->section('posts')
    ->title('My Custom Title')
    ->slug('my-custom-slug')
    ->enabled(true)
    ->postDate('2024-01-15 10:00:00')
    ->create();
```

### Setting Field Values

You can set custom field values on entries using magic methods. Assuming you have a section called 'posts' with a plain text field called 'summary':

```php
use markhuot\craftpest\factories\Entry;

// Set field values using magic methods (field handle)
$entry = Entry::factory()
    ->section('posts')
    ->summary('This is my summary text')
    ->create();
```

### Working with Craft CMS Field Types

Some Craft CMS field types return objects instead of scalar values. When working with these fields in tests, you need to understand how to set values and make assertions on them.

**Detailed documentation for each field type:**

- **[Rich Text Fields](field-types/rich-text.md)** - Working with CKEditor/Redactor fields that return `HtmlFieldData` objects
- **[Link Fields](field-types/link.md)** - Creating various link types (URL, entry, asset, email, phone, SMS) with optional properties
- **[Asset Fields](field-types/assets.md)** - Attaching images, documents, and other files to entries
- **[Matrix Fields](field-types/matrix.md)** - Creating repeatable content blocks with different field combinations
- **[Relation Fields](field-types/relations.md)** - Linking to other entries, categories, users, and elements

**Quick Reference:**

```php
use markhuot\craftpest\factories\{Entry, Asset};

// Rich Text - cast to string for assertions
expect((string)$entry->heading)->toBe('<p>Content</p>');

// Link Field - pass array with type and value
$entry->ctaLink(['type' => 'url', 'value' => 'https://example.com']);

// Asset Field - use ->one() or ->all() to get assets
expect($entry->featuredImage->one())->not->toBeNull();

// Matrix Field - use magic methods or direct factory syntax
$entry->addTextBlockToContentBlocks(heading: 'Title', body: 'Content');

// Relation Field - use ->one(), ->all(), or ->count()
expect($entry->relatedPosts->count())->toBe(3);
```

### Advanced Techniques

#### Using Sequences

Create entries with different values in a sequence:

```php
$entries = Entry::factory()
    ->section('posts')
    ->sequence(
        ['title' => 'First Entry', 'slug' => 'first'],
        ['title' => 'Second Entry', 'slug' => 'second'],
        ['title' => 'Third Entry', 'slug' => 'third'],
    )
    ->count(3)
    ->create();

// Or with a callback
$entries = Entry::factory()
    ->section('posts')
    ->sequence(fn ($index) => [
        'title' => "Entry {$index}",
        'slug' => "entry-{$index}",
    ])
    ->count(10)
    ->create();
```

#### Setting Authors

```php
use markhuot\craftpest\factories\User;

$user = User::factory()->create();

// By object, ID, username, or email
$entry = Entry::factory()->author($user)->create();
$entry = Entry::factory()->author($user->id)->create();
$entry = Entry::factory()->author('username')->create();
$entry = Entry::factory()->author('user@example.com')->create();
```

### Complete Example

Here's a complete test example combining multiple field types. This assumes you have a 'posts' section set up with fields: summary (plain text), featuredImage (asset), relatedPosts (entries), and contentBlocks (matrix with textBlock type):

```php
use markhuot\craftpest\factories\{Entry, Asset};

it('creates a blog post with all field types', function () {
    // Create an asset for the featured image
    $image = Asset::factory()
        ->volume('images')
        ->create();

    // Create some related posts
    $relatedPost1 = Entry::factory()
        ->section('posts')
        ->title('Related Post 1')
        ->create();

    $relatedPost2 = Entry::factory()
        ->section('posts')
        ->title('Related Post 2')
        ->create();

    // Create the main entry with all fields populated
    $entry = Entry::factory()
        ->section('posts')
        ->title('My Blog Post')
        ->summary('This is a summary of the post')
        ->featuredImage($image)
        ->relatedPosts($relatedPost1, $relatedPost2)
        ->addTextBlockToContentBlocks(
            heading: 'Introduction',
            body: 'Welcome to my blog post...',
        )
        ->addTextBlockToContentBlocks(
            heading: 'Main Content',
            body: 'Here is the main content...',
        )
        ->create();

    // Assert the entry was created correctly
    expect($entry->title)->toBe('My Blog Post');
    expect($entry->summary)->toBe('This is a summary of the post');
    expect($entry->featuredImage->one()->id)->toBe($image->id);
    expect($entry->relatedPosts->count())->toBe(2);
    expect($entry->contentBlocks->count())->toBe(2);
});
```

## Browser Testing

Pest provides browser testing capabilities for visual and JavaScript-based testing. **Only use browser tests when regular unit tests or `->get()` requests won't work.**

**When NOT to use browser testing:**

- ❌ Verifying HTML contains expected output (use regular assertions instead)
- ❌ Confirming status codes (use `->get()` with `->assertStatus()`)
- ❌ Confirming a template renders correctly (use `->get()` with HTML assertions)

**When TO use browser testing:**

- ✅ JavaScript execution is required for the test
- ✅ Verifying interactive elements (modals opening/closing, dropdowns, etc.)
- ✅ Testing visual properties (padding, margin, colors, layout)
- ✅ Simulating user interactions (clicks, form submissions with JS validation)

### Basic Browser Test Example

```php
it('opens a modal when button is clicked', function () {
    $page = visit('/page');

    $page->click('@open-modal-button')
        ->waitFor('@modal')
        ->assertVisible('@modal')
        ->assertSee('Modal Content');
});

it('has correct spacing', function () {
    $page = visit('/component');

    $page->assertStyle('@hero-section', 'padding', '2rem');
});
```

### Visiting Templates Directly

Use `visitTemplate()` to render a Twig template directly in the browser without needing a route:

```php
it('renders the hero component correctly', function () {
    $page = $this->visitTemplate('_components/hero', [
        'title' => 'Welcome',
        'subtitle' => 'Hello World',
    ]);

    $page->assertSee('Welcome');
    $page->assertSee('Hello World');
});

it('tests JavaScript interactions in a component', function () {
    $page = $this->visitTemplate('_components/accordion', [
        'items' => [
            ['title' => 'Section 1', 'content' => 'Content 1'],
            ['title' => 'Section 2', 'content' => 'Content 2'],
        ],
    ]);

    // Click to expand the first accordion item
    $page->click('[data-accordion-trigger]:first-child');

    // Wait for animation and verify content is visible
    $page->waitFor('[data-accordion-content]:first-child')
        ->assertVisible('[data-accordion-content]:first-child');
});
```

**Parameters:**
- `$template` (string) - The template path relative to your templates directory (e.g., `'_components/hero'` or `'pages/about'`)
- `$params` (array, optional) - Variables to pass to the template, defaults to `[]`
- `$layout` (string|null, optional) - Layout template to wrap the content in
- `$block` (string|null, optional) - Block name where template content will be rendered

#### Passing Element Objects to Templates

When testing templates that need entries or other elements, you can pass element objects directly. They'll be automatically converted to IDs for transmission and resolved back to full objects during rendering:

```php
use markhuot\craftpest\factories\Entry;

it('renders an entry card component', function () {
    // Create an entry using a factory
    $entry = Entry::factory()
        ->section('posts')
        ->title('My Blog Post')
        ->create();

    // Pass the element object directly - it's automatically converted
    $page = $this->visitTemplate('_components/entry-card', [
        'entry' => $entry,
    ]);

    $page->assertSee('My Blog Post');
});

it('renders a component with multiple elements', function () {
    $author = Entry::factory()
        ->section('authors')
        ->title('John Doe')
        ->create();

    $post = Entry::factory()
        ->section('posts')
        ->title('Great Article')
        ->create();

    // Pass multiple elements
    $page = $this->visitTemplate('_components/post-with-author', [
        'post' => $post,
        'author' => $author,
        'showDate' => true,  // Regular parameters work too
    ]);

    $page->assertSee('Great Article');
    $page->assertSee('John Doe');
});
```

**Alternative Syntax:**

If you prefer explicit control, you can still use the `element:` prefix with element IDs:

```php
it('explicitly passes element IDs', function () {
    $entry = Entry::factory()->create();

    // Both syntaxes work and produce the same result
    $page = $this->visitTemplate('_components/card', [
        'element:entry' => $entry->id,  // Explicit syntax
    ]);
});
```

#### Using Layouts with Templates

Wrap your template in a layout to test how components render within your site structure:

```php
it('renders component within layout', function () {
    $page = $this->visitTemplate('_components/hero', [
        'title' => 'Welcome',
    ], layout: '_layouts/base', block: 'content');

    // Assert layout elements are present
    $page->assertSee('Site Header');  // From layout
    $page->assertSee('Welcome');      // From component
    $page->assertSee('Site Footer');  // From layout
});

// Set a default layout for all visitTemplate() calls
beforeEach(function () {
    $this->setDefaultVisitTemplateLayout('_layouts/base', 'content');
});

it('uses default layout automatically', function () {
    $page = $this->visitTemplate('_components/hero', [
        'title' => 'Hello',
    ]);

    // Layout is automatically applied
    $page->assertSee('Site Header');
    $page->assertSee('Hello');
});
```

**Learn more:** [Pest Browser Testing Documentation](https://pestphp.com/docs/browser-testing)

## Test Organization

### Pest.php Configuration

The `tests/Pest.php` file is used for global configuration:

```php
<?php

uses()->in('Feature');
uses()->in('Unit');

// Set up global expectations
expect()->extend('toBeWithinRange', function (int $min, int $max) {
    return $this->toBeGreaterThanOrEqual($min)
        ->toBeLessThanOrEqual($max);
});
```

## Working with Existing Tests

When modifying or creating tests:

1. **Read existing tests first**: Always use the Read tool to examine current test files to understand patterns and conventions used in the project

2. **Match existing style**: Follow the same structure, naming conventions, and assertion patterns used in the codebase

3. **Run tests after changes**: Always run the test suite after making modifications to ensure nothing breaks

4. **Check for test configuration**: Look for `phpunit.xml` or `pest.xml` files that may contain important configuration

## Debugging Tests

### Dumping Values

```php
test('debugging example', function () {
    $value = ['key' => 'value'];

    \markhuot\craftpest\helpers\test\dump($value);  // Output value and continue
    \markhuot\craftpest\helpers\test\dd($value);    // Output value and die

    expect($value)->toHaveKey('key');
});
```

### Using --filter

```bash
# Run only tests with "HeroComponents" in the name
php ./vendor/bin/pest --filter="HeroComponents"

# Run tests in a specific file
php ./vendor/bin/pest tests/HeroComponentsTest.php
```

### Verbose Output

```bash
# Show more details about test execution
php ./vendor/bin/pest -v
php ./vendor/bin/pest -vv
php ./vendor/bin/pest -vvv
```

## Test Coverage

### Generating Coverage Reports

```bash
# HTML coverage report (opens in browser)
php ./vendor/bin/pest --coverage --coverage-html=coverage

# Terminal coverage report
php ./vendor/bin/pest --coverage

# Enforce minimum coverage
php ./vendor/bin/pest --coverage --min=80
```

## Best Practices

1. **Focus on User Logic, Not Framework Functionality**: Do not write tests that only verify core Craft CMS or Pest functionality. Tests should focus on your custom logic, business rules, or validation.

    **❌ Bad - Testing framework functionality:**

    ```php
    it('can create a hero component entry', function () {
        $entry = Entry::factory()
            ->section('heroComponents')
            ->heading('Test Hero Heading')
            ->description('<p>Test hero description content</p>')
            ->create();

        // These only test that Craft and Pest work, not your code
        expect($entry)->toBeInstanceOf(Entry::class);
        expect($entry->section->handle)->toBe('heroComponents');
        expect((string)$entry->heading)->toBe('Test Hero Heading');
        expect((string)$entry->description)->toContain('Test hero description');
    });
    ```

    **✅ Good - Testing custom validation or business logic:**

    ```php
    it('validates that heading cannot exceed 100 characters', function () {
        $entry = Entry::factory()
            ->section('heroComponents')
            ->heading(str_repeat('a', 101))
            ->create();

        expect($entry->getErrors('heading'))->not->toBeEmpty();
    });

    it('renders hero component with formatted output', function () {
        $entry = Entry::factory()
            ->section('heroComponents')
            ->heading('Test Heading')
            ->create();

        $output = renderHeroComponent($entry);

        expect($output)->toContain('<h1>Test Heading</h1>');
        expect($output)->toContain('hero-component-wrapper');
    });
    ```

    Only create entries with factories as setup for testing your actual logic. If you're not testing custom validation, rendering logic, or business rules, you probably don't need the test.

2. **Test Naming**: Use descriptive test names that explain what is being tested

    ```php
    it('renders hero component with correct title and description', function () {
        // ...
    });
    ```

3. **Single Assertion Per Test**: When possible, test one thing at a time

    ```php
    it('has a title', function () {
        expect($component->title)->not->toBeNull();
    });

    it('has a description', function () {
        expect($component->description)->not->toBeNull();
    });
    ```

4. **Use Datasets for Similar Tests**: Reduce code duplication with datasets

    ```php
    it('validates input', function ($input, $expected) {
        expect(validate($input))->toBe($expected);
    })->with([
        ['valid', true],
        ['invalid', false],
    ]);
    ```

5. **Arrange-Act-Assert Pattern**: Structure tests clearly

    ```php
    it('creates a user', function () {
        // Arrange
        $data = ['name' => 'John', 'email' => 'john@example.com'];

        // Act
        $user = createUser($data);

        // Assert
        expect($user->name)->toBe('John');
        expect($user->email)->toBe('john@example.com');
    });
    ```

6. **Clean Up After Tests**: Use `afterEach()` or `afterAll()` to clean up resources

7. **Skip Tests When Needed**: Use `skip()` to temporarily disable tests
    ```php
    it('has a pending feature', function () {
        // Test implementation
    })->skip('Waiting for API changes');
    ```

## Common Issues

### Memory Limit

For large test suites, you may need to increase PHP memory limit:

```bash
php -d memory_limit=512M ./vendor/bin/pest
```

### Xdebug

If tests are slow, check if Xdebug is enabled. Disable it for faster execution:

```bash
php -d xdebug.mode=off ./vendor/bin/pest
```

## Additional Resources

- Official Pest Documentation: https://pestphp.com
- Craft specific Documentation: https://craft-pest.com
- Expectation API: https://pestphp.com/docs/expectations

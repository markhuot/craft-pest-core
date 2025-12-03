# Rich Text Fields

Rich Text fields return `HtmlFieldData` objects. When making assertions on these fields, you need to cast them to strings.

## Basic Usage

```php
use markhuot\craftpest\factories\Entry;

$entry = Entry::factory()
    ->section('posts')
    ->heading('<p>To be or not to be</p>')
    ->create();

// Cast to string for assertion
expect((string)$entry->heading)->toBe('<p>To be or not to be</p>');
```

## Making Assertions

```php
// Cast to string when checking content
expect((string)$entry->richTextField)->toContain('expected text');

// Check if field has content
expect((string)$entry->richTextField)->not->toBeEmpty();

// Check for specific HTML tags
expect((string)$entry->richTextField)->toContain('<p>');
expect((string)$entry->richTextField)->toMatch('/<h[1-6]>/');
```

## Common Patterns

```php
// Set rich text content
$entry = Entry::factory()
    ->section('posts')
    ->content('<h2>Heading</h2><p>Paragraph text</p>')
    ->create();

// Multiple paragraphs
$entry = Entry::factory()
    ->section('posts')
    ->description('<p>First paragraph</p><p>Second paragraph</p>')
    ->create();

// With HTML entities
$entry = Entry::factory()
    ->section('posts')
    ->summary('<p>Price: &pound;100</p>')
    ->create();

expect((string)$entry->summary)->toContain('&pound;');
```

## Important Notes

- **Always cast to string**: Rich text fields return `HtmlFieldData` objects, not strings
- **HTML is preserved**: The field stores HTML, so expect tags in your assertions
- **Use `toContain()` for partial matches**: When you only care about specific content
- **Use `toBe()` for exact matches**: When you need to verify the complete HTML output

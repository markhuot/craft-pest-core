# Factories

Factories in Craft Pest provide a powerful way to create test data for your Craft CMS projects. They allow you to quickly generate entries, fields, sections, assets, and other elements with realistic test data.

## Important: Section Types

**Factories should only be used with Channel and Structure section types.** Singles cannot be created via factories because there can only be one instance of each Single section.

For Single sections, retrieve the existing entry using Craft's standard query syntax:

```php
use craft\elements\Entry;

// Get a Single entry
$homepage = Entry::find()
    ->section('homepage')
    ->one();

// Modify and test it
$homepage->setFieldValue('heroHeading', 'New Heading');
Craft::$app->elements->saveElement($homepage);

expect($homepage->heroHeading)->toBe('New Heading');
```

## Basic Entry Creation

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

### Make vs Create

- `make()` - Creates instances without saving to the database
- `create()` - Creates instances and saves them to the database

```php
// Create without saving
$entry = Entry::factory()->make();

// Create and save
$entry = Entry::factory()->create();
```

### Creating Multiple Entries

```php
// Create 5 entries
$entries = Entry::factory()
    ->section('posts')
    ->count(5)
    ->create();  // Returns a Collection when count > 1
```

## Setting Basic Entry Properties

```php
$entry = Entry::factory()
    ->section('posts')
    ->title('My Custom Title')
    ->slug('my-custom-slug')
    ->enabled(true)
    ->postDate('2024-01-15 10:00:00')
    ->create();
```

## Setting Field Values

You can set custom field values on entries using magic methods. Assuming you have a section called 'posts' with a plain text field called 'summary':

```php
use markhuot\craftpest\factories\Entry;

// Set field values using magic methods (field handle)
$entry = Entry::factory()
    ->section('posts')
    ->summary('This is my summary text')
    ->create();
```

## Working with Craft CMS Field Types

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

## Advanced Techniques

### Using Sequences

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

### Setting Authors

```php
use markhuot\craftpest\factories\User;

$user = User::factory()->create();

// By object, ID, username, or email
$entry = Entry::factory()->author($user)->create();
$entry = Entry::factory()->author($user->id)->create();
$entry = Entry::factory()->author('username')->create();
$entry = Entry::factory()->author('user@example.com')->create();
```

## Complete Example

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

# Matrix Fields

Matrix fields are one of Craft's most powerful field types, allowing you to create repeatable blocks of content with different field combinations. Craft Pest provides two ways to add matrix blocks to your entries.

## Method 1: Magic Shorthand (Recommended)

The magic method pattern `add[BlockTypeName]To[MatrixFieldName]()` provides a convenient shorthand:

```php
use markhuot\craftpest\factories\Entry;

// Pattern: add[BlockTypeName]To[MatrixFieldName]()
$entry = Entry::factory()
    ->section('posts')
    ->addTextBlockToContentBlocks(
        heading: 'My Heading',
        body: 'My body content',
    )
    ->create();
```

### Adding Multiple Blocks

Add multiple blocks by chaining method calls:

```php
$entry = Entry::factory()
    ->section('posts')
    ->addTextBlockToContentBlocks(
        heading: 'Introduction',
        body: 'Welcome to my post',
    )
    ->addTextBlockToContentBlocks(
        heading: 'Main Content',
        body: 'Here is the main content',
    )
    ->addImageBlockToContentBlocks(
        image: Asset::factory()->volume('images'),
        caption: 'A beautiful image'
    )
    ->create();
```

### Important Limitation

**If your block type handle or matrix field handle contains the word "To" (case-insensitive), the magic method parser may not work correctly.** In those cases, use Method 2 below.

## Method 2: Direct Factory Syntax

When your handles contain "To", or if you prefer explicit syntax, use the direct factory approach:

### For Craft 4

Use `Block::factory()`:

```php
use markhuot\craftpest\factories\{Entry, Block};

$entry = Entry::factory()
    ->section('posts')
    ->contentBlocks(
        Block::factory()
            ->type('textBlock')
            ->heading('My Heading')
            ->body('My body content')
    )
    ->create();
```

### For Craft 5+

Use `Entry::factory()`:

```php
use markhuot\craftpest\factories\Entry;

$entry = Entry::factory()
    ->section('posts')
    ->contentBlocks(
        Entry::factory()
            ->type('textBlock')
            ->heading('My Heading')
            ->body('My body content')
    )
    ->create();
```

### Adding Multiple Blocks

Pass multiple factory instances:

```php
$entry = Entry::factory()
    ->section('posts')
    ->contentBlocks(
        Entry::factory()
            ->type('textBlock')
            ->heading('Introduction')
            ->body('Welcome to my post'),
        Entry::factory()
            ->type('textBlock')
            ->heading('Main Content')
            ->body('Here is the main content'),
        Entry::factory()
            ->type('imageBlock')
            ->image(Asset::factory()->volume('images'))
            ->caption('A beautiful image')
    )
    ->create();
```

## Creating Multiple Blocks with Count

The direct syntax allows you to create multiple blocks using `->count()`:

```php
// Create 5 blocks of the same type
$entry = Entry::factory()
    ->section('posts')
    ->contentBlocks(
        Entry::factory()
            ->type('textBlock')
            ->heading('Repeated Block')
            ->body('This content repeats')
            ->count(5)
    )
    ->create();
```

## Making Assertions on Matrix Fields

Matrix fields return `ElementCollection` objects. Access blocks directly:

```php
// Check block count
expect($entry->contentBlocks->count())->toBe(2);

// Access individual blocks
$firstBlock = $entry->contentBlocks->first();
expect($firstBlock->type->handle)->toBe('textBlock');
expect((string)$firstBlock->heading)->toBe('Introduction');

// Iterate through blocks
foreach ($entry->contentBlocks as $block) {
    expect($block->type->handle)->toBeIn(['textBlock', 'imageBlock']);
}

// Check if specific block type exists
$hasImageBlock = $entry->contentBlocks
    ->filter(fn($block) => $block->type->handle === 'imageBlock')
    ->isNotEmpty();
expect($hasImageBlock)->toBeTrue();
```

## Working with Nested Matrix Fields

You can nest matrix blocks within other blocks:

```php
$entry = Entry::factory()
    ->section('posts')
    ->addTextBlockToContentBlocks(
        heading: 'Section 1',
        body: 'Content here'
    )
    ->create();

// Access nested matrix field on a block
$block = $entry->contentBlocks->first();
if ($block->type->handle === 'textBlock') {
    // Add nested blocks if the block type has its own matrix field
    expect($block->nestedContent)->not->toBeNull();
}
```

## Common Patterns

### Mixed Block Types

```php
use markhuot\craftpest\factories\{Entry, Asset};

$entry = Entry::factory()
    ->section('posts')
    ->addTextBlockToContentBlocks(
        heading: 'Introduction',
        body: 'Welcome to the post'
    )
    ->addImageBlockToContentBlocks(
        image: Asset::factory()->volume('images'),
        caption: 'Hero image'
    )
    ->addQuoteBlockToContentBlocks(
        quote: 'To be or not to be',
        author: 'Shakespeare'
    )
    ->addTextBlockToContentBlocks(
        heading: 'Conclusion',
        body: 'Thanks for reading'
    )
    ->create();

expect($entry->contentBlocks->count())->toBe(4);
```

### Blocks with Relation Fields

```php
$relatedEntry = Entry::factory()->section('resources')->create();

$entry = Entry::factory()
    ->section('posts')
    ->addResourceBlockToContentBlocks(
        title: 'Related Resource',
        resource: $relatedEntry
    )
    ->create();

$block = $entry->contentBlocks->first();
expect($block->resource->one()->id)->toBe($relatedEntry->id);
```

### Blocks with Rich Text

Remember to cast rich text fields to strings:

```php
$entry = Entry::factory()
    ->section('posts')
    ->addTextBlockToContentBlocks(
        heading: '<h2>My Heading</h2>',
        body: '<p>Paragraph content</p>'
    )
    ->create();

$block = $entry->contentBlocks->first();
expect((string)$block->heading)->toBe('<h2>My Heading</h2>');
expect((string)$block->body)->toContain('Paragraph');
```

## Important Notes

- **Matrix fields return ElementCollection**: Access blocks directly without calling `->all()` or `->one()`
- **Block types must exist**: The block type handle must be defined in your matrix field
- **Method naming matters**: For magic methods, use exact PascalCase for block types and camelCase for field handles
- **Craft version differences**: Craft 4 uses `Block::factory()`, Craft 5+ uses `Entry::factory()` for blocks
- **Named parameters**: Use named parameters for better readability when adding blocks

# Asset Fields

Asset fields allow you to attach images, documents, videos, and other files to your entries. This guide covers creating assets and attaching them to entries in your tests.

## Basic Asset Creation

Assuming you have an existing volume called 'images':

```php
use markhuot\craftpest\factories\Asset;

// Create an asset (default: 500x500px gray square)
$asset = Asset::factory()
    ->volume('images')
    ->create();

// Create an asset from a specific file
$asset = Asset::factory()
    ->volume('images')
    ->source('/path/to/image.jpg')
    ->create();
```

## Attaching Assets to Entries

### Single Asset Field

```php
use markhuot\craftpest\factories\{Entry, Asset};

// Create asset first, then attach to entry
$asset = Asset::factory()
    ->volume('images')
    ->create();

$entry = Entry::factory()
    ->section('posts')
    ->featuredImage($asset)
    ->create();
```

### Multiple Assets Field

```php
// Attach multiple assets to a field
$asset1 = Asset::factory()->volume('images')->create();
$asset2 = Asset::factory()->volume('images')->create();
$asset3 = Asset::factory()->volume('images')->create();

$entry = Entry::factory()
    ->section('posts')
    ->galleryImages($asset1, $asset2, $asset3)
    ->create();
```

## Creating Assets Inline

You can create assets inline when creating entries:

```php
$entry = Entry::factory()
    ->section('posts')
    ->featuredImage(
        Asset::factory()->volume('images')
    )
    ->create();

// Multiple inline assets
$entry = Entry::factory()
    ->section('posts')
    ->galleryImages(
        Asset::factory()->volume('images'),
        Asset::factory()->volume('images'),
        Asset::factory()->volume('images')
    )
    ->create();
```

## Making Assertions on Asset Fields

Asset fields return `ElementQuery` objects. Use `->one()` or `->all()` to get the actual assets:

```php
// Single asset field
expect($entry->featuredImage->one())->not->toBeNull();
expect($entry->featuredImage->one()->id)->toBe($asset->id);
expect($entry->featuredImage->one()->volume->handle)->toBe('images');

// Multiple assets field
expect($entry->galleryImages->count())->toBe(3);
expect($entry->galleryImages->all())->toHaveCount(3);

// Check specific asset is attached
$assetIds = $entry->galleryImages->ids();
expect($assetIds)->toContain($asset1->id);
```

## Asset Properties

You can set and assert various asset properties:

```php
// Create asset with properties
$asset = Asset::factory()
    ->volume('images')
    ->title('My Image')
    ->filename('my-image.jpg')
    ->create();

// Assert properties
expect($asset->title)->toBe('My Image');
expect($asset->filename)->toBe('my-image.jpg');
expect($asset->extension)->toBe('jpg');
expect($asset->kind)->toBe('image');
```

## Working with Different Volume Types

```php
// Documents volume
$document = Asset::factory()
    ->volume('documents')
    ->source('/path/to/document.pdf')
    ->create();

// Videos volume
$video = Asset::factory()
    ->volume('videos')
    ->source('/path/to/video.mp4')
    ->create();

// Any file type
$file = Asset::factory()
    ->volume('files')
    ->source('/path/to/file.zip')
    ->create();
```

## Common Patterns

```php
// Create entry with hero image
$entry = Entry::factory()
    ->section('posts')
    ->title('My Post')
    ->featuredImage(
        Asset::factory()
            ->volume('images')
            ->title('Hero Image')
    )
    ->create();

expect($entry->featuredImage->one()->title)->toBe('Hero Image');

// Create entry with multiple images and assert count
$entry = Entry::factory()
    ->section('posts')
    ->galleryImages(
        Asset::factory()->volume('images')->count(5)
    )
    ->create();

expect($entry->galleryImages->count())->toBe(5);
```

## Important Notes

- **Asset fields return ElementQuery**: Always use `->one()` or `->all()` to get asset objects
- **Default asset is a gray square**: If no source is provided, a 500x500px gray square is created
- **Volume must exist**: The specified volume handle must exist in your Craft installation
- **Source paths**: When providing a source path, ensure the file exists and is accessible

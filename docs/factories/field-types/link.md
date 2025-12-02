# Link Fields

Link fields in Craft CMS allow users to create various types of links (URLs, entries, assets, email, phone, etc.). When working with Link fields in tests, you need to provide an array with the link type and value.

## Basic Link Field Usage

```php
use markhuot\craftpest\factories\Entry;

// URL link
// IMPORTANT: URL values must be complete URLs with protocol (https://...)
// Relative URIs like '/contact' will fail - use 'https://example.com/contact'
$entry = Entry::factory()
    ->section('heroComponents')
    ->ctaLink([
        'type' => 'url',
        'value' => 'https://example.com'
    ])
    ->create();

// Entry link (linking to another entry)
$targetEntry = Entry::factory()->section('pages')->create();
$entry = Entry::factory()
    ->section('heroComponents')
    ->ctaLink([
        'type' => 'entry',
        'value' => $targetEntry->id
    ])
    ->create();

// Email link
$entry = Entry::factory()
    ->section('heroComponents')
    ->ctaLink([
        'type' => 'email',
        'value' => 'contact@example.com'
    ])
    ->create();
```

## Available Link Types

- `'url'` - External or internal URL (must be complete URL with protocol, e.g., `https://example.com/page` not `/page`)
- `'entry'` - Link to a Craft entry (use entry ID as value)
- `'asset'` - Link to a Craft asset (use asset ID as value)
- `'category'` - Link to a Craft category (use category ID as value)
- `'email'` - Email address
- `'phone'` - Phone number
- `'sms'` - SMS number

## Link Fields with Optional Properties

```php
// URL link with label, target, and other attributes
$entry = Entry::factory()
    ->section('heroComponents')
    ->ctaLink([
        'type' => 'url',
        'value' => 'https://craftcms.com',
        'label' => 'Visit Craft CMS',
        'target' => '_blank',
        'title' => 'Opens in new tab',
        'class' => 'btn btn-primary',
        'rel' => 'nofollow',
        'ariaLabel' => 'Visit Craft CMS website'
    ])
    ->create();

// Download link
$asset = Asset::factory()->volume('documents')->create();
$entry = Entry::factory()
    ->section('heroComponents')
    ->ctaLink([
        'type' => 'asset',
        'value' => $asset->id,
        'download' => true,
        'filename' => 'document.pdf'
    ])
    ->create();
```

## Making Assertions on Link Fields

Link fields return `LinkData` objects. Access properties using object notation:

```php
// Assert link properties
expect($entry->ctaLink->type)->toBe('url');
expect($entry->ctaLink->value)->toBe('https://example.com');
expect($entry->ctaLink->label)->toBe('Visit Craft CMS');
expect($entry->ctaLink->target)->toBe('_blank');

// Get the rendered URL
expect($entry->ctaLink->url)->toBe('https://example.com');

// Check if link is set
expect($entry->ctaLink)->not->toBeNull();
```

## Optional Link Properties

All available optional properties:

- **`label`** - Custom link text
- **`urlSuffix`** - Query parameters or hash anchor (e.g., `'?param=value'` or `'#section'`)
- **`target`** - Target attribute (e.g., `'_blank'`, `'_self'`)
- **`title`** - Title attribute for accessibility
- **`class`** - CSS classes
- **`id`** - HTML ID attribute
- **`rel`** - Rel attribute (e.g., `'nofollow'`, `'noopener'`)
- **`ariaLabel`** - ARIA label for accessibility
- **`download`** - Boolean to enable download attribute
- **`filename`** - Custom filename for downloads

## Common Patterns

```php
// Phone link
$entry = Entry::factory()
    ->section('contact')
    ->phoneLink([
        'type' => 'phone',
        'value' => '+1-555-123-4567',
        'label' => 'Call Us'
    ])
    ->create();

// SMS link
$entry = Entry::factory()
    ->section('contact')
    ->smsLink([
        'type' => 'sms',
        'value' => '+1-555-123-4567',
        'label' => 'Text Us'
    ])
    ->create();

// Category link
$category = Category::factory()->group('topics')->create();
$entry = Entry::factory()
    ->section('posts')
    ->categoryLink([
        'type' => 'category',
        'value' => $category->id,
        'label' => 'View Category'
    ])
    ->create();
```

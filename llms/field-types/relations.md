# Relation Fields (Entries, Categories, Users)

Relation fields allow you to link entries to other elements in Craft CMS, such as other entries, categories, users, or products. This guide covers working with Entries fields, Categories fields, and Users fields in your tests.

## Basic Entries Field Usage

Assuming you have a section 'posts' with an entries field called 'relatedPosts':

```php
use markhuot\craftpest\factories\Entry;

// Create some related entries
$related1 = Entry::factory()->section('posts')->create();
$related2 = Entry::factory()->section('posts')->create();

// Attach relations using magic method (by entry object)
$entry = Entry::factory()
    ->section('posts')
    ->relatedPosts($related1, $related2)
    ->create();
```

## Different Ways to Attach Relations

### By Entry Object

```php
$relatedEntry = Entry::factory()->section('posts')->create();

$entry = Entry::factory()
    ->section('posts')
    ->relatedPosts($relatedEntry)
    ->create();
```

### By Entry ID

```php
$relatedEntry = Entry::factory()->section('posts')->create();

$entry = Entry::factory()
    ->section('posts')
    ->relatedPosts($relatedEntry->id)
    ->create();
```

### Create Related Entries Inline

```php
$entry = Entry::factory()
    ->section('posts')
    ->relatedPosts(
        Entry::factory()->section('posts'),
        Entry::factory()->section('posts')
    )
    ->create();
```

### Mix Objects and IDs

```php
$existing = Entry::factory()->section('posts')->create();

$entry = Entry::factory()
    ->section('posts')
    ->relatedPosts(
        $existing,                              // By object
        $existing->id,                          // By ID
        Entry::factory()->section('posts')      // Inline factory
    )
    ->create();
```

## Working with Categories Fields

```php
use markhuot\craftpest\factories\{Entry, Category};

// Create categories
$category1 = Category::factory()->group('topics')->create();
$category2 = Category::factory()->group('topics')->create();

// Attach to entry
$entry = Entry::factory()
    ->section('posts')
    ->categories($category1, $category2)
    ->create();

// Or inline
$entry = Entry::factory()
    ->section('posts')
    ->categories(
        Category::factory()->group('topics'),
        Category::factory()->group('topics')
    )
    ->create();
```

## Working with Users Fields

```php
use markhuot\craftpest\factories\{Entry, User};

// Create users
$author = User::factory()->create();
$editor = User::factory()->create();

// Attach to entry
$entry = Entry::factory()
    ->section('posts')
    ->contributors($author, $editor)
    ->create();

// Or inline
$entry = Entry::factory()
    ->section('posts')
    ->contributors(
        User::factory(),
        User::factory()
    )
    ->create();
```

## Making Assertions on Relation Fields

Relation fields return `ElementQuery` objects. Use `->one()`, `->all()`, or `->count()`:

```php
// Check count
expect($entry->relatedPosts->count())->toBe(2);

// Get all related entries
$related = $entry->relatedPosts->all();
expect($related)->toHaveCount(2);

// Get first related entry
$first = $entry->relatedPosts->one();
expect($first)->not->toBeNull();
expect($first->section->handle)->toBe('posts');

// Check if specific entry is related
$relatedIds = $entry->relatedPosts->ids();
expect($relatedIds)->toContain($related1->id);

// Check if any relations exist
expect($entry->relatedPosts->exists())->toBeTrue();
```

## Working with Single Relation Fields

Some fields only allow a single related element:

```php
// Single entry field
$author = Entry::factory()->section('authors')->create();

$entry = Entry::factory()
    ->section('posts')
    ->primaryAuthor($author)
    ->create();

// Assert single relation
expect($entry->primaryAuthor->one())->not->toBeNull();
expect($entry->primaryAuthor->one()->id)->toBe($author->id);

// For single relations, you might only pass one element
$entry = Entry::factory()
    ->section('posts')
    ->featuredPost(Entry::factory()->section('posts'))
    ->create();
```

## Common Patterns

### Create Entry with Multiple Relation Types

```php
use markhuot\craftpest\factories\{Entry, Category, User, Asset};

$author = User::factory()->create();
$category = Category::factory()->group('topics')->create();
$featuredImage = Asset::factory()->volume('images')->create();
$relatedPost = Entry::factory()->section('posts')->create();

$entry = Entry::factory()
    ->section('posts')
    ->title('My Post')
    ->author($author)
    ->categories($category)
    ->featuredImage($featuredImage)
    ->relatedPosts($relatedPost)
    ->create();

expect($entry->author->id)->toBe($author->id);
expect($entry->categories->count())->toBe(1);
expect($entry->featuredImage->one()->id)->toBe($featuredImage->id);
expect($entry->relatedPosts->count())->toBe(1);
```

### Circular Relations

```php
$post1 = Entry::factory()->section('posts')->create();
$post2 = Entry::factory()->section('posts')->create();

// Link them to each other
$post1->relatedPosts = [$post2->id];
$post1->save();

$post2->relatedPosts = [$post1->id];
$post2->save();

expect($post1->relatedPosts->one()->id)->toBe($post2->id);
expect($post2->relatedPosts->one()->id)->toBe($post1->id);
```

### Relations with Specific Sources

When your relation field is limited to specific sections/groups:

```php
// Entries field limited to 'resources' section
$resource = Entry::factory()->section('resources')->create();

$entry = Entry::factory()
    ->section('posts')
    ->resources($resource)
    ->create();

expect($entry->resources->one()->section->handle)->toBe('resources');

// Categories field limited to 'topics' group
$topic = Category::factory()->group('topics')->create();

$entry = Entry::factory()
    ->section('posts')
    ->topics($topic)
    ->create();

expect($entry->topics->one()->group->handle)->toBe('topics');
```

### Testing Relation Ordering

```php
$first = Entry::factory()->section('posts')->title('First')->create();
$second = Entry::factory()->section('posts')->title('Second')->create();
$third = Entry::factory()->section('posts')->title('Third')->create();

$entry = Entry::factory()
    ->section('posts')
    ->relatedPosts($first, $second, $third)
    ->create();

// Relations maintain order
$related = $entry->relatedPosts->all();
expect($related[0]->title)->toBe('First');
expect($related[1]->title)->toBe('Second');
expect($related[2]->title)->toBe('Third');
```

## Important Notes

- **Relation fields return ElementQuery**: Always use `->one()`, `->all()`, or `->count()` to get actual elements
- **Pass multiple elements**: Relation fields accept multiple elements as separate arguments
- **Inline factories**: You can create related elements inline without calling `->create()` first
- **Check existence**: Use `->exists()` to check if any relations are set
- **IDs vs Objects**: Both work - pass whatever is most convenient for your test
- **Order is preserved**: Relations maintain the order you specify when creating the entry

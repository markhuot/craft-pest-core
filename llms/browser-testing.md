# Browser Testing

Pest provides browser testing capabilities for visual and JavaScript-based testing. **Only use browser tests when regular unit tests or `->get()` requests won't work.**

## When to Use Browser Testing

**When NOT to use browser testing:**

- ❌ Verifying HTML contains expected output (use regular assertions instead)
- ❌ Confirming status codes (use `->get()` with `->assertStatus()`)
- ❌ Confirming a template renders correctly (use `->get()` with HTML assertions)

**When TO use browser testing:**

- ✅ JavaScript execution is required for the test
- ✅ Verifying interactive elements (modals opening/closing, dropdowns, etc.)
- ✅ Testing visual properties (padding, margin, colors, layout)
- ✅ Simulating user interactions (clicks, form submissions with JS validation)

## Basic Browser Test Example

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

## Visiting Templates Directly

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

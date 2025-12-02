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

**Learn more:** [Pest Browser Testing Documentation](https://pestphp.com/docs/browser-testing)

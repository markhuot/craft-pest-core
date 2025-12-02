# Rendering Templates Directly

Use `->renderTemplate()` to render Twig templates directly without the overhead of a full HTTP request. This is faster and more focused than `->get()` when you only need to test template output.

## Basic Template Rendering

```php
<?php

it('renders a template', function () {
    $this->renderTemplate('pages/home')
        ->assertSee('Welcome');
});

// Can be chained in a fluent style
it('renders the hero component')
    ->renderTemplate('_components/hero')
    ->assertSee('Hero Content');
```

## Passing Variables to Templates

Pass variables as the second parameter, just like you would in Twig:

```php
<?php

it('renders a template with variables', function () {
    $this->renderTemplate('_components/card', [
        'title' => 'My Card Title',
        'description' => 'Card description text',
    ])
        ->assertSee('My Card Title')
        ->assertSee('Card description text');
});

// Pass complex data
it('renders a list with entries', function () {
    $entries = Entry::factory()
        ->section('posts')
        ->count(3)
        ->create();

    $this->renderTemplate('_partials/entry-list', [
        'entries' => $entries,
    ])
        ->assertSee($entries[0]->title)
        ->assertSee($entries[1]->title)
        ->assertSee($entries[2]->title);
});
```

## All HTTP Assertions Work

Since `renderTemplate()` returns a `TestableResponse` object (same as `->get()` and `->post()`), all the same assertions are available:

```php
<?php

it('uses various assertions on rendered templates', function () {
    $response = $this->renderTemplate('_components/hero', [
        'heading' => 'Welcome',
        'showCta' => true,
    ]);

    // Content assertions
    $response->assertSee('Welcome');
    $response->assertDontSee('Hidden Content');

    // Access the raw content
    expect($response->content)->toContain('<h1>');
});
```

## DOM Selection and Testing

Use `->querySelector()` to select and test specific elements:

```php
<?php

it('selects and tests specific elements', function () {
    $this->renderTemplate('_components/navigation')
        ->querySelector('nav ul')
        ->assertSee('Home')
        ->assertSee('About');
});

it('tests element attributes', function () {
    $this->renderTemplate('_components/button', ['type' => 'primary'])
        ->querySelector('button')
        ->assertAttribute('class', 'btn-primary');
});

it('tests nested elements')
    ->renderTemplate('_layouts/page')
    ->querySelector('header h1')
    ->assertSee('Page Title');
```

## Snapshot Testing

Templates work great with snapshot testing:

```php
<?php

it('matches template snapshot', function () {
    $this->renderTemplate('_components/card', [
        'title' => 'Test Card',
        'content' => 'Test content',
    ])
        ->assertMatchesSnapshot();
});

it('matches element snapshot', function () {
    $this->renderTemplate('_partials/footer')
        ->querySelector('.copyright')
        ->assertMatchesSnapshot();
});
```

## When to Use renderTemplate() vs get() vs Browser Testing

**Use `->renderTemplate()` when:**
- ✅ Testing template logic in isolation
- ✅ Testing components or partials that don't have their own routes
- ✅ You need faster tests (no HTTP overhead)
- ✅ Testing pure template rendering without controllers or routing

**Use `->get()` when:**
- ✅ Testing full request/response cycles
- ✅ Testing routing, controllers, or middleware
- ✅ Testing with authentication or session state
- ✅ The route performs logic before rendering

**Use browser testing (`visit()` or `visitTemplate()`) when:**
- ✅ Testing JavaScript interactions
- ✅ Testing visual properties (CSS, layout, spacing)
- ✅ Testing dynamic behavior that requires a browser

See [Browser Testing Documentation](browser-testing.md) for comprehensive information on browser testing and [Making Requests](making-requests.md) for HTTP request testing.

## Best Practices

1. **Test Template Logic, Not Framework Features**: Focus on your custom Twig logic, conditionals, loops, and output

    ```php
    <?php

    // ✅ Good - Testing custom conditional logic
    it('shows CTA when flag is enabled', function () {
        $this->renderTemplate('_components/hero', ['showCta' => true])
            ->assertSee('Call to Action');

        $this->renderTemplate('_components/hero', ['showCta' => false])
            ->assertDontSee('Call to Action');
    });

    // ❌ Bad - Just testing that Twig works
    it('renders a template', function () {
        $this->renderTemplate('_components/hero')
            ->assertSee('Hero');
    });
    ```

2. **Use for Component Testing**: Perfect for testing reusable components in isolation

    ```php
    <?php

    it('renders alert component with different types', function ($type, $expected) {
        $this->renderTemplate('_components/alert', ['type' => $type])
            ->assertSee($expected);
    })->with([
        ['success', 'alert-success'],
        ['error', 'alert-error'],
        ['warning', 'alert-warning'],
    ]);
    ```

3. **Combine with Factories**: Use factories to create realistic test data

    ```php
    <?php

    it('renders entry card with all fields', function () {
        $entry = Entry::factory()
            ->section('articles')
            ->title('Test Article')
            ->description('Article description')
            ->featuredImage($imageId)
            ->create();

        $this->renderTemplate('_components/entry-card', ['entry' => $entry])
            ->assertSee('Test Article')
            ->assertSee('Article description')
            ->querySelector('img')
            ->assertAttribute('src');
    });
    ```

4. **Template Paths**: Use relative paths from your templates directory

    ```php
    <?php

    // Correct paths
    $this->renderTemplate('pages/home')           // templates/pages/home.twig
    $this->renderTemplate('_components/hero')      // templates/_components/hero.twig
    $this->renderTemplate('_partials/nav')         // templates/_partials/nav.twig
    ```

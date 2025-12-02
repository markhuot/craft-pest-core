# Requests

You can simulate requests to the Craft application via several helper methods
on the `TestCase` as well as via the standalone `Http` helpers. The most
common helper is `$this->get('uri')` or `get('uri')`. This will make a request
to Craft at the given `'uri'` and return a [`TestableResponse`](assertions/response.md).

> **Note:** If you only need to test template rendering without routing, controllers, or
> full HTTP request cycles, consider using `->renderTemplate()` instead. It's faster and
> more focused. See [Rendering Templates](rendering-templates.md) for details.

You can kick off a request in a classic test,

```php
it ('gets something', function () {
  $this->get('/')->assertOk();
});
```

Using Pest's higher order proxies you can do the same thing without a closure,

```php
it('gets something')
  ->get('/')
  ->assertOk();
```

And, lastly, you can skip the description all together and use a descriptionless
test.

```php
use function markhuot\craftpest\helpers\Http\get;

get('/')->assertOk();
```

All of these are functionally identical. You are free to select the syntax that reads
the most naturally for your test and provides the right context for the test. For
more information on the test context see, [Getting Started](getting-started.md).

## get(string $uri)
Makes a `GET` request to Craft.

## post(string $uri, array $body = array ())
Makes a `POST` request to Craft.

```php
$this->post('/comments', [
  'author' => '...',
  'body' => '...',
])->assertOk();
```

Because _many_ `POST` requests need to send the CSRF token along with the
request it is handled automatically within the `->post()` method. If
you would prefer to handle this yourself you may use the raw `->http()` method
insetad. The above `/comments` example is functionally similar to,

```php
$this->http('post', '/comments')
  ->withCsrfToken()
  ->setBody(['author' => '...', 'body' => '...'])
  ->send()
  ->assertOk();
```

## postJson(string $uri, array $body = array ())
Similar to `->post()`, while adding aJSON `content-type` and `accept` headers.

```php
$this->postJson('/comments', [
  'author' => '...',
  'body' => '...',
])->assertOk();
```

## action(string $action, array $body = array ())
Maes a `POST` request to Craft with the `action` param filled in to the
passed value.

## http(string $method, string $uri)
Generate a raw HTTP request without any conventions applied.

## Common Response Assertions

All HTTP request methods (`get()`, `post()`, `postJson()`, etc.) return a `TestableResponse` object that provides many assertion methods:

### Status Code Assertions

```php
<?php

$this->get('/')->assertOk();                      // 200
$this->post('/comments')->assertCreated();        // 201
$this->get('/admin')->assertForbidden();          // 403
$this->get('/missing')->assertNotFound();         // 404
```

### Content Assertions

```php
<?php

$this->get('/')
    ->assertSee('Welcome')              // Response contains text
    ->assertDontSee('Hidden');          // Response doesn't contain text
```

### JSON Assertions

```php
<?php

// Contains JSON subset
$this->get('/api/users')
    ->assertJson(['status' => 'success']);

// Exact JSON match
$this->get('/api/user/1')
    ->assertExactJson(['id' => 1, 'name' => 'John']);

// Assert value at JSON path
$this->get('/api/users')
    ->assertJsonPath('users.0.name', 'John');

// Assert JSON array count
$this->get('/api/users')
    ->assertJsonCount(5, 'users');

// Assert JSON structure
$this->get('/api/users')
    ->assertJsonStructure([
        'users' => [
            '*' => ['id', 'name', 'email']
        ]
    ]);

// Contains JSON fragment
$this->get('/api/users')
    ->assertJsonFragment(['name' => 'John']);

// JSON doesn't contain value
$this->get('/api/users')
    ->assertJsonMissing(['role' => 'admin']);

// Path doesn't exist in JSON
$this->get('/api/users')
    ->assertJsonMissingPath('users.secret');
```

### Header Assertions

```php
<?php

$this->get('/api/data')
    ->assertHeader('content-type')                    // Header exists
    ->assertHeader('content-type', 'application/json') // Header has value
    ->assertHeaderMissing('x-debug');                 // Header doesn't exist
```

### Cookie Assertions

```php
<?php

$this->get('/login')
    ->assertCookie('session')                      // Cookie exists
    ->assertCookie('session', 'abc123')            // Cookie has value
    ->assertCookieMissing('old-cookie')            // Cookie doesn't exist
    ->assertCookieExpired('expired-session')       // Cookie is expired
    ->assertCookieNotExpired('active-session');    // Cookie is valid
```

### Other Assertions

```php
<?php

// Response is a file download
$this->get('/download/file.pdf')
    ->assertDownload('file.pdf');

// Response has cache tags
$this->get('/cached-page')
    ->assertCacheTag('pages', 'homepage');
```

## Authenticated Requests

Use `actingAs()` to make requests as a specific user:

```php
<?php

use markhuot\craftpest\factories\User;

it('allows authenticated users to access protected pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/settings')
        ->assertOk();
});

// Shorthand for admin users
it('allows admins to access settings')
    ->actingAsAdmin()
    ->get('/admin/settings')
    ->assertOk();
```

See [Logging In](logging-in.md) for more authentication helpers.

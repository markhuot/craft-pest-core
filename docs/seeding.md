# Seeding

You can pre-fill your database with sample data via Seeders. Seeders are simply invokable functions
or classes. Within the seeder you are free to interact with the database however you need. Although, most
commonly, you will use factories to generate sample data.

```php
class DatabaseSeeder {
    public function __invoke() {
        Entry::factory()->section('news')->count(10)->create();
    }
}
```

## Console command

Seeders can be run manually via the `php craft pest/seed` console command.

By default, your application seeders live in the `modules\pest\seeders` namespace with the default seeder
being the `DatabaseSeeder`. Out of the box, running `php craft pest/seed` will invoke the
`modules\pest\seeders\DatabaseSeeder` class.

You can override the default namespace either by passing a `--namespace` option to the console command or by
setting a `PEST_SEEDER_NAMESPACE` environment variable. The following two commands are functionally identical.

```bash
$ php craft pest/seed --namespace="my\\custom\\namespace"
$ PEST_SEEDER_NAMESPACE="my\\custom\\namespace" php craft pest/seed
```

The default seeder can be overridden by passing the class name as an argument to the `seed` command.

```bash
$ php craft pest/seed MyAlternateSeeder
```

Note, that if you pass a seeder that begins with a forward slash it will be treated as a fully qualified class
name and the default namespace will be ignored.

```bash
$ php craft pest/seed \\my\\custom\\Seeder
```

## Test setup

Tests can invoke one or more seeders by passing the fully qualified class name to the `->seed()`
method. For example,

```php
it('has entries', function () {
    // ...
})->seed(\modules\pest\seeders\DatabaseSeeder::class);
```

The seed method also accepts any PHP callable, so you can pass any other invokable class or a
callable. Each of the following should produce the same result.

```php
function functionSeeder() {
    return Entry::factory()->section('news')->count(10)->create();
}

$anonymousFunctionSeeder = function () {
    return Entry::factory()->section('news')->count(10)->create();
};

$classSeeder = new class {
    public function __invoke() {
        return Entry::factory()->section('news')->count(10)->create();
    }
};

it('has entries', function () {
    // ...
})->seed(functionSeeder(...), $anonymousFunctionSeeder, $classSeeder);
```

Within a test any data that the seeder returns will be exposed to the test via the `$this->seedData` property. It is
not required that a seeder return data, however, in which case the `$this->seedData` property will be `null`.

```php
it('has entries', function () {
    $this->assertNotEmpty($this->seedData);
})->seed(\modules\pest\seeders\DatabaseSeeder::class);
```

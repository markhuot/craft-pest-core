# CLI Commands

## actionTest()
Run the Pest tests with `php craft pest/test`. This is a convenience function that internally calls the
`pest/init` method and then `./vendor/bin/pest` executable.

You may pass any pest options to this command by separating them with a `--`. For example, to filter
down to a specific test you may run `php craft pest -- --filter="renders the homepage"`.

## actionInit()
Running `php craft pest/init` will create the `tests` directory, an associated `tests/Pest.php` file, a
default `phpunit.xml` file, and a `modules/pest/seeders` directory. If any of these files or directories
already exist they will be skipped.

This command id idempotent and can be run multiple times without issue. If you even want to reset your
setup to the default `Pest.php`, for example, you can delete your `Pest.php` and re-run `php craft pest/init`
to have the file recreated.

## actionSeed($seeder = NULL)
Pest comes with a built-in database seeder that can be called in your own tests or via the command
line. You may run the seeder with `php craft pest/seed`. By default, this will look for a class
called \modules\pest\seeders\DatabaseSeeder. You may override this by passing a fully qualified class
name as the first argument. For example, `php craft pest/seed \\modules\\pest\\seeders\\UserSeeder`.

Seeders are __invoke-able classes. Inside the invoke method you are free to seed your database however
you would like, although commonly you'll use factories to create your data. For example:

```php
class DatabaseSeeder
{
    public function __invoke()
    {
        return \markhuot\craftpest\factories\Entry::factory()->count(10)->create();
    }
}
```

You can override the defaults with the following environment variables,

```bash
PEST_SEEDER_NAMESPACE="\modules\pest\seeders"
PEST_DEFAULT_SEEDER=DatabaseSeeder
```

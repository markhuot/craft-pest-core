<?php

namespace modules\pest\seeders;

use markhuot\craftpest\factories\Entry;
use markhuot\craftpest\seeders\Seeder;

/**
 * Seed the database with test data. Here you can call model factories to fill the database
 * with test data you can reason against.
 *
 * You can run this seeder on the cli with
 *
 * ```
 * php craft pest/seed
 * ```
 *
 * Or, you can run seeds per-test via the `->seed()` method.
 *
 *```php
 * it('has seed data', function () {})->seed(new DatabaseSeeder);
 *```
 */
class DatabaseSeeder extends Seeder
{
    public function __invoke()
    {
        // Entry::factory()->section('news')->count(10)->create();
    }
}

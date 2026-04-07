<?php

namespace markhuot\craftpest\seeders;

use markhuot\craftpest\factories\Entry;

class DatabaseSeeder
{
    public function __invoke()
    {
        return Entry::factory()->section('posts')->create();
    }
}

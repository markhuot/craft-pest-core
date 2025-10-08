<?php

namespace markhuot\craftpest\seeders;

class DatabaseSeeder
{
    public function __invoke()
    {
        return \markhuot\craftpest\factories\Entry::factory()->section('posts')->create();
    }
}

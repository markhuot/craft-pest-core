<?php

namespace markhuot\craftpest\seeders;

use markhuot\craftpest\actions\CallSeeders;

class Seeder
{
    public function call(...$seeders)
    {
        return (new CallSeeders)->handle(...$seeders);
    }
}

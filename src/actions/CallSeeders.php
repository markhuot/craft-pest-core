<?php

namespace markhuot\craftpest\actions;

class CallSeeders
{
    public function handle(...$seeders)
    {
        $seedData = [];

        foreach ($seeders as $seeder) {
            if (is_string($seeder) && class_exists($seeder)) {
                $seedData[] = (new $seeder)($this);
            }
            else if (is_callable($seeder)) {
                $seedData[] = $seeder($this);
            }
            else {
                throw new \RuntimeException('Invalid seed');
            }
        }

        return collect($seedData);
    }
}

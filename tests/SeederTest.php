<?php

use craft\elements\Entry;
use markhuot\craftpest\seeders\DatabaseSeeder;

$functionSeed = fn() => \markhuot\craftpest\factories\Entry::factory()->create();

$classSeed = new class {
    public function __invoke() {
        return \markhuot\craftpest\factories\Entry::factory()->create();
    }
};

it('runs function seeders', function () {
    expect(Entry::find()->one()->id)->toBe($this->seedData->first()?->id);
})->seed($functionSeed(...));

it('runs class name seeders', function () {
    expect(Entry::find()->one()->id)->toBe($this->seedData->first()?->id);
})->seed(DatabaseSeeder::class);

it('runs class instance seeders', function () {
    expect(Entry::find()->one()->id)->toBe($this->seedData->first()?->id);
})->seed($classSeed(...));

it('runs multiple seeders', function () {
    expect((int)Entry::find()->count())->toBe(2);
})->seed($functionSeed(...), $classSeed(...));

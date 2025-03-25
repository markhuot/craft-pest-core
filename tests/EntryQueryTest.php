<?php

use markhuot\craftpest\factories\Entry;
use markhuot\craftpest\factories\Section;

it('counts entries', function () {
    Entry::factory()
        ->section($section = Section::factory()->create())
        ->count(10)
        ->create();

    \craft\elements\Entry::find()->section($section)->assertCount(10);
});

<?php

use markhuot\craftpest\factories\Entry;

it('creates provisional drafts', function () {
    $entry = Entry::factory()
        ->isProvisionalDraft()
        ->create();

    expect($entry)->isProvisionalDraft->toBeTrue();
});

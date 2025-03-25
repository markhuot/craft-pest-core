<?php

use markhuot\craftpest\factories\Entry;

it('assert queue jobs are pushed', function () {
    Entry::factory()->create();

    $this->assertJob(\craft\queue\jobs\PruneRevisions::class);
});

it('assert queue jobs by job properties', function () {
    $entry = Entry::factory()->create();

    $this->assertJob(fn (\craft\queue\jobs\PruneRevisions $job) => $job->canonicalId === $entry->id);
});

it('assert queue jobs in order', function () {
    $entry1 = Entry::factory()->create();
    $entry2 = Entry::factory()->create();

    $this->assertJob(
        fn (\craft\queue\jobs\PruneRevisions $job) => $job->canonicalId === $entry1->id,
        fn (\craft\queue\jobs\PruneRevisions $job) => $job->canonicalId === $entry2->id,
    );
});

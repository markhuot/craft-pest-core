# Queues

You can assert certain jobs are pushed on to the queue during your tests to validate
that Craft is queuing the correct jobs for your use case.

First, it is recommended to set an environment variable in your `phpunit.xml` file to
override your QUEUE_DRIVER=sync.

With that done you can use `assertJob(...)` to assert that the specified queue job was
added to the queue during the test.

Note, Craft's queue system handles queues at the end of the request cycle, when using
the Sync queue driver.

```php
it('runs queues', function() {
    Entry::factory()->create();

    // This is run implicitly for you, as the very last step
    // Craft::$app->queue->run();

    // So you can now assert against the run jobs
    $this->assertJob(\craft\queue\jobs\UpdateSearchIndex::class);
});
```
```

## assertJob(callable|string $assertions)
You can assert a job was added to the queue by passing in one or more matching
patterns. A matching pattern can either be a string representing the class name
of the job OR a callable that accepts a job and returns true if it matches.

As long as one job matches the pattern the test will pass. If no jobs match the
pattern the test will fail.

If you pass multiple jobs they must match in same order as the arguments to
`assertJob`.

For example,

```php
$this->assertJob(\craft\queue\jobs\PruneRevisions::class);
$this->assertJob(\craft\queue\jobs\PruneRevisions::class, \craft\queue\jobs\UpdateSearchIndex::class);
$this->assertJob(fn (PruneRevisions $job) => $job->canonicalId === $entry->id);
```

Note: when passing a callable with a type-hinted job parameter the system will automatically filter
out any jobs not matching the type-hint.

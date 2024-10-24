# Queue Jobs

Any `Sync` queue jobs are automatically run at the end of each test. Essentially the equivilant of,

```php
it('runs queues', function() {
  mock(UpdateSearchIndex::class)
    ->shouldReceive('execute')
    ->once();

  // Act and perform your test
  Entry::factory()->create();

  // This is run implicitely for you, as the very last step
  // Craft::$app->queue->run();
});
```

This test confirms that when an entry is created the `UpdateSearchIndex` job is run. You could just as easily test that a custom action or custom listener is run when an entry matching certain conditions is run.

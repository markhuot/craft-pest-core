# Queue Jobs

Any `Sync` queue jobs are automatically run at the end of each test. Essentially thr equivilant of,

```php
it('runs queues', function() {
  Entry::factory()->create();

  // This is run implicitely for you
  Craft::$app->queue->run();
});
```

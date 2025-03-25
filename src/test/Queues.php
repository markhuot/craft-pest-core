<?php

namespace markhuot\craftpest\test;

use Craft;
use craft\base\Event;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use ReflectionNamedType;
use yii\queue\ExecEvent;
use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * # Queues
 *
 * You can assert certain jobs are pushed on to the queue during your tests to validate
 * that Craft is queuing the correct jobs for your use case.
 *
 * First, it is recommended to set an environment variable in your `phpunit.xml` file to
 * override your QUEUE_DRIVER=sync.
 *
 * With that done you can use `assertJob(...)` to assert that the specified queue job was
 * added to the queue during the test.
 *
 * Note, Craft's queue system handles queues at the end of the request cycle, when using
 * the Sync queue driver.
 *
 * ```php
 * it('runs queues', function() {
 *     Entry::factory()->create();
 *
 *     // This is run implicitly for you, as the very last step
 *     // Craft::$app->queue->run();
 *
 *     // So you can now assert against the run jobs
 *     $this->assertJob(\craft\queue\jobs\UpdateSearchIndex::class);
 * });
 * ```
 * ```
 */
trait Queues
{
    /**
     * @var Collection<array-key, ExecEvent>
     */
    protected Collection $payloads;

    /**
     * @var array<array-key, array<array-key, callable>>
     */
    protected array $assertions = [];

    public function setUpQueues()
    {
        $this->payloads = collect();

        Event::on(Queue::class, Queue::EVENT_BEFORE_EXEC, function (ExecEvent $event) {
            $this->payloads->push($event);
        });
    }

    protected function assertPostConditions(): void
    {
        $queue = Craft::$app->queue;

        if ($queue instanceof \yii\queue\sync\Queue) {
            $queue->run();
        }

        $this->performAssertions();

        parent::assertPostConditions();
    }

    /**
     * You can assert a job was added to the queue by passing in one or more matching
     * patterns. A matching pattern can either be a string representing the class name
     * of the job OR a callable that accepts a job and returns true if it matches.
     *
     * As long as one job matches the pattern the test will pass. If no jobs match the
     * pattern the test will fail.
     *
     * If you pass multiple jobs they must match in same order as the arguments to
     * `assertJob`.
     *
     * For example,
     *
     * ```php
     * $this->assertJob(\craft\queue\jobs\PruneRevisions::class);
     * $this->assertJob(\craft\queue\jobs\PruneRevisions::class, \craft\queue\jobs\UpdateSearchIndex::class);
     * $this->assertJob(fn (PruneRevisions $job) => $job->canonicalId === $entry->id);
     * ```
     *
     * Note: when passing a callable with a type-hinted job parameter the system will automatically filter
     * out any jobs not matching the type-hint.
     *
     * @param  array<array-key, string|callable>|string|callable  $assertions
     */
    public function assertJob(string|callable ...$assertions)
    {
        foreach ($assertions as &$assertion) {
            if (is_string($assertion)) {
                $assertion = fn (JobInterface $job) => get_class($job) === $assertion;
            }
        }

        $this->assertions[] = $assertions;
    }

    protected function performAssertions()
    {
        foreach ($this->assertions as $assertions) {
            $this->performAssertion($assertions);
        }
    }

    protected function performAssertion(array $steps)
    {
        $payloadIndex = 0;

        foreach ($steps as $step) {
            for ($i = $payloadIndex; $i < $this->payloads->count(); $i++) {
                $reflect = new \ReflectionFunction($step);
                $type = $reflect->getParameters()[0]->getType();
                if ($type && $type instanceof ReflectionNamedType) {
                    $className = $type->getName();
                    if (! ($this->payloads[$i]->job instanceof $className)) {
                        continue;
                    }
                }

                if ($step($this->payloads[$i]->job, $this->payloads[$i])) {
                    $payloadIndex = $i;

                    continue 2; // continue to the next step
                }
            }

            Assert::assertTrue(false, 'A queue step was not matched.');
        }

        Assert::assertTrue(true);
    }
}

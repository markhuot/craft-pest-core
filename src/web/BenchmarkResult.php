<?php

namespace markhuot\craftpest\web;

use craft\debug\Module;
use PHPUnit\Framework\Assert;

/**
 * # Benchmarks
 *
 * Benchmarks can be taken on Craft actions which you can then assert against. For example you
 * may want to load the homepage and ensure there are no duplicate queries that could have been
 * lazy loaded. You would do this with,
 *
 * ```php
 * it('checks for duplicate queries')
 *   ->beginBenchmark()
 *   ->get('/')
 *   ->endBenchmark()
 *   ->assertNoDuplicateQueries();
 * ```
 *
 * @see \markhuot\craftpest\test\Benchmark
 */
class BenchmarkResult
{
    protected $manifestCache;

    public function __construct(
        public int $startProfileAt,
        public int $endProfileAt,
    ) {
    }

    // function summary()
    // {
    //     $dbQueries = $this->messages->filter(function($message) {
    //         return $message[2] === Command::class . '::query';
    //     });
    //     $timings = collect(\Craft::getLogger()->calculateTimings($dbQueries))
    //         ->sortByDesc('duration');
    //     echo 'There were ' . $dbQueries->count() . ' queries'."\n";
    //     echo 'Slowest Query ' . $timings->first()['duration'] . ' seconds: ' . $timings->first()['info']."\n";
    //     echo 'Duplicate queries ' . $timings->duplicates('info')->count()."\n";
    //     return $this;
    // }

    public function getQueries()
    {
        $logs = array_slice(\Craft::getLogger()->getProfiling(), $this->startProfileAt, $this->endProfileAt - $this->startProfileAt);

        return collect($logs)
            ->filter(fn ($log) => in_array($log['category'], \Craft::getLogger()->dbEventNames));
    }

    public function assertQueryCount(int $expected)
    {
        $queries = $this->getQueries();
        $actual = $queries->count();

        Assert::assertEquals($expected, $actual, 'The expected query count, '.$expected.' did not match the actual query count, '.$actual.PHP_EOL.'- '.$queries->pluck('info')->join(PHP_EOL.'- '));
    }

    public function getQueryTiming()
    {
        return collect($this->getPanels()['db']->calculateTimings());
    }

    public function getDuplicateQueries()
    {
        return $this->getQueryTiming()->filter(function ($query) {
            return preg_match('/^SHOW/', $query['info']) === false;
        })->duplicates('info');
    }

    public function getPanels()
    {
        $logTarget = Module::getInstance()->logTarget;

        if (empty($this->manifestCache)) {
            $this->manifestCache = $logTarget->loadManifest();
        }

        $tags = array_keys($this->manifestCache);

        if (empty($tags)) {
            throw new \Exception('No debug data have been collected yet, try browsing the website first.');
        }

        $tag = reset($tags);

        $logTarget->loadTagToPanels($tag);

        return Module::getInstance()->panels;
    }

    /**
     * Ensures there are no duplicate queries since the benchmark began.
     *
     * ```php
     * $benchmark->assertNoDuplicateQueries();
     * ```
     */
    public function assertNoDuplicateQueries()
    {
        $duplicates = $this->getDuplicateQueries();

        Assert::assertSame(
            0,
            $duplicates->count(),
            'Duplicate queries were found during the test. '."\n".$duplicates->first()
        );

        return $this;
    }

    /**
     * Assert that the execution timing of the benchmark is less than the given timing
     * in seconds.
     *
     * > **Note**
     * > Benchmarks must begin and end in your test. That allows you to do any necessary
     * > setup before the benchmark begins so your test preamble doesn't affect your assertion.
     *
     * ```php
     * it('loads an article', function () {
     *   $entry = Entry::factory()->section('articles')->create();
     *
     *   $this->beginBenchmark()
     *     ->get($entry->uri)
     *     ->endBenchmark()
     *     ->assertLoadTimeLessThan(2);
     * });
     * ```
     */
    public function assertLoadTimeLessThan(float $expectedLoadTime)
    {
        $actualLoadTime = $this->getPanels()['profiling']->data['time'];

        Assert::assertLessThan($expectedLoadTime, $actualLoadTime);

        return $this;
    }

    /**
     * Assert that the peak memory load of the benchmark is less than the given memory limit
     * in megabytes.
     *
     *
     * ```php
     * it('loads the homepage')
     *   ->beginBenchmark()
     *   ->get('/');
     *   ->endBenchmark()
     *   ->assertMemoryLoadLessThan(128);
     * });
     * ```
     */
    public function assertMemoryLoadLessThan(float $expectedMemoryLoad)
    {
        $actualMemoryLoadBytes = $this->getPanels()['profiling']->data['memory'];
        $actualMemoryLoadMb = $actualMemoryLoadBytes / 1024 / 1024;

        Assert::assertLessThan($expectedMemoryLoad, $actualMemoryLoadMb);

        return $this;
    }

    /**
     * Assert that every query is faster than the given threshold in seconds.
     *
     *
     * ```php
     * it('loads the homepage')
     *   ->beginBenchmark()
     *   ->get('/');
     *   ->endBenchmark()
     *   ->assertAllQueriesFasterThan(0.05);
     * });
     * ```
     */
    public function assertAllQueriesFasterThan(float $expectedQueryTime)
    {
        $failing = $this->getQueryTiming()->filter(function ($query) use ($expectedQueryTime) {
            return (float) $query['duration'] > $expectedQueryTime;
        });

        if ($failing->count()) {
            Assert::fail($failing->count().' queries were slower than '.$expectedQueryTime);
        }

        expect(true)->toBe(true);
    }
}

<?php

it('benchmarks duplicate queries')
    ->beginBenchmark()
    ->get('/')
    ->assertOk()
    ->endBenchmark()
    ->assertNoDuplicateQueries();

it('benchmarks query speed')
    ->beginBenchmark()
    ->get('/')
    ->assertOk()
    ->endBenchmark()
    ->assertAllQueriesFasterThan(2);

it('benchmarks load time')
    ->beginBenchmark()
    ->get('/')
    ->assertOk()
    ->endBenchmark()
    ->assertLoadTimeLessThan(10);

it('benchmarks memory usage')
    ->beginBenchmark()
    ->get('/')
    ->assertOk()
    ->endBenchmark()
    ->assertMemoryLoadLessThan(256);

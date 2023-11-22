<?php

namespace markhuot\craftpest\test;

/**
 * # Snapshots
 *
 * A variety of snapshot assertions are available to help you test your HTML and DOM output in craft-pest. In
 * many places you can simply expect an object `->toMatchSnapshot()` and Pest will handle the rest for you.
 *
 * For example, responses, DOM Lists, and views are all snapshotable.
 *
 * ```php
 * it('matches responses')->get('/')->assertMatchesSnapshot();
 * it('matches dom lists')->get('/')->querySelector('h1')->assertMatchesSnapshot();
 * it('matches views')->renderTemplate('_news/entry', $variables)->assertMatchesSnapshot();
 * ```
 *
 * ## Elements
 *
 * Many elements can be snapshotted as well. Unfortunately, Pest is not smart enough to properly
 * snapshot elements so you must call `->toSnapshot()` on them first.
 *
 * ```php
 * it('imports entries', function () {
 *   $this->importEntries();
 *   $entries = Entry::find()->section('news')->collect();
 *
 *   expect($entries->map->toSnapshot())->toMatchSnapshot();
 * });
 * ```
 */
trait SnapshotAssertions
{
    public function assertMatchesSnapshot()
    {
        expect($this)->toMatchSnapshot();
    }
}

<?php

namespace markhuot\craftpest\test;

/**
 * # Snapshots
 *
 * A variety of snapshot assertions are available to help you test your HTML and DOM output in craft-pest. In
 * many places you can simply expect an object `->toMatchSnapshot()` and Pest will handle the rest for you.
 *
 * The two entrypoints to snapshotting are,
 * - `expect($object)->toMatchSnapshot()`
 * - `$this->assertMatchesSnapshot()`
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
 * Many elements can be snapshotted as well. When using assertions Pest will automatically handle the
 * conversion from an Element to a snapshot.
 *
 * ```php
 * Entry::factory()->title('foo')->create()->assertMatchesSnapshot();
 * ```
 *
 * Unfortunately, Pest is not smart enough to properly snapshot elements in an expectation so you must
 * call `->toSnapshot()` on them first.
 *
 * ```php
 * it('imports entries', function () {
 *   $this->importEntries();
 *   $entry = Entry::find()->section('news')->one();
 *
 *   expect($entry->toSnapshot())->toMatchSnapshot();
 * });
 * ```
 *
 * ## Attributes
 *
 * Only a subset of attributes from the element are snapshotted so dynamic fields do not create
 * unexpected failures. For example, the `$entry->postDate` defaults to the current time when
 * the entry was generated. That means running the test on Tuesday and again on Wednesday would
 * fail the test because the `postDate` would be Tuesday in the initial snapshot and Wednesday
 * during the comparison.
 *
 * If you'd like to include additional attributes in the snapshot you can pass them as an array
 * to the `->toSnapshot()` method. For example,
 *
 * ```php
 * it('snapshots postDate', function () {
 *   $entry = Entry::factory()->postDate('2022-01-01')->create();
 *
 *   expect($entry->toSnapshot(['postDate']))->toMatchSnapshot();
 *   $entry->assertMatchesSnapshot(['postDate']);
 * });
 * ```
 */
trait SnapshotAssertions
{
    public function assertMatchesSnapshot(): self
    {
        expect($this)->toMatchSnapshot();

        return $this;
    }
}

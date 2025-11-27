<?php

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use markhuot\craftpest\factories\Entry;

function varySnapshots(string $description, callable $test)
{
    it('craft4: '.$description, $test)->skip(! InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~4.7.0'));
    it('craft5: '.$description, $test)->skip(! InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~5.8.0'));
}

it('asserts html snapshots')
    ->get('/selectors')
    ->assertOk()
    ->assertMatchesSnapshot();

it('expects html snapshots', function () {
    $response = $this->get('/selectors')->assertOk();

    expect($response)->toMatchSnapshot();
})->skip;

it('asserts dom snapshots')
    ->get('/selectors')
    ->assertOk()
    ->querySelector('ul')
    ->assertMatchesSnapshot();

it('expects dom snapshots', function () {
    $dom = $this->get('/selectors')->assertOk()
        ->querySelector('ul');

    expect($dom)->assertMatchesSnapshot();
});

it('asserts view snapshots')
    ->renderTemplate('selectors')
    ->assertMatchesSnapshot();

it('asserts view dom snapshots')
    ->renderTemplate('selectors')
    ->querySelector('h1')
    ->assertMatchesSnapshot();

it('renders views with variables')
    ->renderTemplate('variable', ['foo' => 'bar'])
    ->assertMatchesSnapshot();

varySnapshots('matches nested entry snapshots', function () {
    $child = Entry::factory()
        ->section('posts')
        ->title('child');

    $parent = Entry::factory()
        ->section('posts')
        ->title('foo bar')
        ->entriesField([$child])
        ->create();

    $entry = \craft\elements\Entry::find()->id($parent->id)->with(['entriesField'])->one();

    expect($entry)->toMatchSnapshot();
});

varySnapshots('matches collected snapshots', function () {
    Entry::factory()->section('posts')->count(3)->sequence(fn ($index) => ['title' => 'Entry '.$index])->create();
    $entries = \craft\elements\Entry::find()->section('posts')->collect();

    $snapshots = collect($entries->map->toSnapshotArray())->sortBy('title')->values()->all();
    expect(json_encode($snapshots))->toMatchSnapshot();
});

// Before 5.3.3 Craft reported isNewForSite as `true` after a save but thanks to
// https://github.com/craftcms/cms/issues/15517 it switched to `false. So, to avoid splitting our
// tests we don't actually check snapshots on newly created elements. We re-fetch them from
// the database to be sure we're not running in to differences with isNewForSite between
// Craft versions
varySnapshots('includes postDate in snapshots', function () {
    $entry = Entry::factory()
        ->section('posts')
        ->postDate('2022-01-01 00:00:00')
        ->title('foo bar')
        ->create();

    expect(\craft\elements\Entry::find()->id($entry->id)->one())
        ->toSnapshot(['postDate'])
        ->toMatchSnapshot();
});

varySnapshots('includes postDate in snapshot assertions', function () {
    $entry = Entry::factory()
        ->section('posts')
        ->postDate('2022-01-01 00:00:00')
        ->title('foo bar')
        ->create();

    expect(\craft\elements\Entry::find()->id($entry->id)->one())
        ->assertMatchesSnapshot(['postDate']);
});

varySnapshots('matches entry snapshots', function () {
    $entry = Entry::factory()
        ->section('posts')
        ->title('foo bar')
        ->textField('foo')
        ->dropdownField('one')
        ->create();

    expect(\craft\elements\Entry::find()->id($entry->id)->one())
        ->toMatchSnapshot();
});

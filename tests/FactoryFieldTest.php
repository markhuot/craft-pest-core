<?php

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use markhuot\craftpest\factories\Entry;
use markhuot\craftpest\factories\Field;
use markhuot\craftpest\factories\Section;

it('creates entries and rolls back', function () {
    $section = Section::factory()->create();
    $entryCount = \craft\elements\Entry::find()->count();
    Entry::factory()->section($section->handle)->count(5)->create();

    test()->rollBackTransaction();
    expect(\craft\elements\Entry::find()->count())->toBe($entryCount);
});

it('creates fields and rolls back', function () {
    Field::factory()->type(\craft\fields\PlainText::class)->create();

    $section = Section::factory()->create();
    $entryCount = \craft\elements\Entry::find()->count();
    Entry::factory()->section($section->handle)->count(5)->create();

    test()->rollBackTransaction();
    expect(\craft\elements\Entry::find()->count())->toBe($entryCount);
});

it('errors when trying to create fields after content elements in Craft 4', function () {
    $this->expectException(\markhuot\craftpest\exceptions\AutoCommittingFieldsException::class);

    Section::factory()->create();
    Field::factory()->type(\craft\fields\PlainText::class)->create();
})->skip(InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '>=5.0.0'));

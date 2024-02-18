<?php

use Craft;
use markhuot\craftpest\factories\Entry;

it('asserts database content', function () {
    $count = (new \craft\db\Query)->from(\craft\db\Table::SITES)->count();
    $this->assertDatabaseCount(\craft\db\Table::SITES, $count);
});

it('asserts database content on condition', function () {
    $section = \markhuot\craftpest\factories\Section::factory()->create();
    $entry = \markhuot\craftpest\factories\Entry::factory()->section($section)->create();

    $table = version_compare(Craft::$app->version, '5.0.0', '>=') ? \craft\db\Table::ELEMENTS_SITES : \craft\db\Table::CONTENT;
    $this->assertDatabaseHas($table, [
        'title' => $entry->title,
    ]);
});

it('asserts database content is missing', function () {
    $table = version_compare(Craft::$app->version, '5.0.0', '>=') ? \craft\db\Table::ELEMENTS_SITES : \craft\db\Table::CONTENT;
     $this->assertDatabaseMissing($table, ['title' => 'fooz baz']);
});

it('asserts trashed', function () {
    $entry = Entry::factory()
        ->create()
        ->assertNotTrashed();

    \Craft::$app->elements->deleteElement($entry);
    $entry->assertTrashed();
});

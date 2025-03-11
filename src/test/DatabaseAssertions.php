<?php

namespace markhuot\craftpest\test;

use craft\base\Element;
use markhuot\craftpest\test\Assert;

/**
 * # Database Assertions
 *
 * You can assert that particular rows appear in the database using database assertions.
 */
trait DatabaseAssertions
{
    /**
     * Check that the given table contains the given number of rows.
     *
     * ```php
     * $this->assertDatabaseCount('{{%entries}}', 6);
     * ```
     */
    public function assertDatabaseCount(string $tableName, int $expectedCount): void
    {
        Assert::assertDatabaseCount($tableName, $expectedCount);
    }

    /**
     * Check that the given table contains one or more matching rows
     * for the given condition.
     *
     * ```php
     * $this->assertDatabaseHas('{{%content}}', ['title' => 'My Great Title']);
     * ```
     */
    public function assertDatabaseHas(string $tableName, array $condition): void
    {
        Assert::assertDatabaseHas($tableName, $condition);
    }

    /**
     * Check that the given table contains zero matching rows
     * for the given condition.
     *
     * ```php
     * $this->assertDatabaseMissing('{{%content}}', ['title' => 'My Great Title']);
     * ```
     */
    public function assertDatabaseMissing(string $tableName, array $condition): void
    {
        Assert::assertDatabaseMissing($tableName, $condition);
    }

    /**
     * Check that the given element has been trashed (soft deleted).
     *
     * ```php
     * $this->assertTrashed($entry);
     * ```
     */
    public function assertTrashed(Element $element): void
    {
        Assert::assertTrashed($element);
    }

    /**
     * Check that the given element has not been trashed (soft deleted).
     *
     * ```php
     * $this->assertNotTrashed($entry);
     * ```
     */
    public function assertNotTrashed(Element $element): void
    {
        Assert::assertNotTrashed($element);
    }
}

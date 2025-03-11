<?php

namespace markhuot\craftpest\test;

use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnitAssert;

class Assert extends PHPUnitAssert
{
    /**
     * Check that the given table contains the given number of rows.
     */
    public static function assertDatabaseCount(string $tableName, int $expectedCount): void
    {
        $actualCount = (new Query)->from($tableName)->count();
        static::assertEquals($expectedCount, $actualCount);
    }

    /**
     * Check that the given table contains one or more matching rows
     * for the given condition.
     */
    public static function assertDatabaseHas(string $tableName, array $condition): void
    {
        $actualCount = (new Query)->from($tableName)->where($condition)->count();
        static::assertGreaterThanOrEqual(1, $actualCount);
    }

    /**
     * Check that the given table contains zero matching rows
     * for the given condition.
     */
    public static function assertDatabaseMissing(string $tableName, array $condition): void
    {
        $actualCount = (new Query)->from($tableName)->where($condition)->count();
        static::assertSame(0, (int) $actualCount);
    }

    /**
     * Check that the given element has been trashed (soft deleted).
     */
    public static function assertTrashed(Element $element): void
    {
        $row = (new Query)->from(Table::ELEMENTS)->where(['id' => $element->id])->one();
        static::assertNotEmpty($row['dateDeleted']);
    }

    /**
     * Check that the given element has not been trashed (soft deleted).
     */
    public static function assertNotTrashed(Element $element): void
    {
        $row = (new Query)->from(Table::ELEMENTS)->where(['id' => $element->id])->one();
        static::assertEmpty($row['dateDeleted']);
    }

    /**
     * Asserts that the element is valid (contains no errors from validation).
     * If $keys is provided, only checks that the specified keys contain no errors.
     */
    public static function assertValid(Element $element, array $keys = []): void
    {
        if ($keys === []) {
            static::assertCount(0, $element->errors);
            return;
        }

        $errors = collect($keys)
            ->mapWithKeys(fn ($key) => [$key => $element->getErrors($key)])
            ->filter(fn ($errors): bool => $errors !== []);

        if ($errors->count()) {
            static::fail('The following keys were expected to be valid but had errors: '.implode(', ', $errors->keys()->all()));
        }
    }

    /**
     * Asserts that the element is invalid (contains errors from validation).
     */
    public static function assertInvalid(Element $element, array $keys = []): void
    {
        if ($keys === []) {
            static::assertGreaterThanOrEqual(1, count($element->errors));
            return;
        }

        $errors = collect($keys)
            ->mapWithKeys(fn ($key) => [$key => $element->getErrors($key)])
            ->filter(fn ($errors): bool => $errors === []);

        if ($errors->count()) {
            static::fail('The following keys were expected to be invalid but were not: '.implode(', ', $errors->keys()->all()));
            return;
        }

        static::assertTrue(true);
    }
}

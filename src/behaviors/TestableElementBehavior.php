<?php

namespace markhuot\craftpest\behaviors;

use yii\base\Behavior;
use markhuot\craftpest\test\Assert as CraftAssert;

use function markhuot\craftpest\helpers\test\test;

/**
 * # Elements
 *
 * Elements, like entries, and be tested in Craft via the following assertions.
 *
 * @property \craft\base\Element $owner
 */
class TestableElementBehavior extends Behavior
{
    /**
     * Asserts that the element is valid (contains no errors from validation).
     *
     * > **Note**
     * > Since validation errors throw Exceptions in Pest, by default, you must
     * > silence those exceptions to continue the test.
     *
     * ```php
     * Entry::factory()
     *   ->create()
     *   ->assertValid()
     * ```
     */
    public function assertValid(array $keys = [])
    {
        CraftAssert::assertValid($this->owner, $keys);

        return $this->owner;
    }

    /**
     * Asserts that the element is invalid (contains errors from validation).
     *
     * ```php
     * Entry::factory()
     *   ->muteValidationErrors()
     *   ->create(['title' => null])
     *   ->assertInvalid();
     * ```
     */
    public function assertInvalid(array $keys = [])
    {
        CraftAssert::assertInvalid($this->owner, $keys);

        return $this->owner;
    }

    /**
     * Check that the element has its `dateDeleted` flag set
     *
     * ```php
     * $entry = Entry::factory()->create();
     * \Craft::$app->elements->deleteElement($entry);
     * $entry->assertTrashed();
     * ```
     */
    public function assertTrashed()
    {
        CraftAssert::assertTrashed($this->owner);

        return $this->owner;
    }

    /**
     * Check that the element does not have its `dateDeleted` flag set
     *
     * ```php
     * Entry::factory()->create()->assertNotTrashed();
     * ```
     */
    public function assertNotTrashed()
    {
        CraftAssert::assertNotTrashed($this->owner);

        return $this->owner;
    }
}

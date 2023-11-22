<?php

namespace markhuot\craftpest\web;

use markhuot\craftpest\behaviors\ExpectableBehavior;
use markhuot\craftpest\behaviors\TestableResponseBehavior;
use markhuot\craftpest\test\Dd;
use markhuot\craftpest\test\SnapshotAssertions;

/**
 * @mixin ExpectableBehavior
 * @mixin TestableResponseBehavior
 */
class TestableResponse extends \craft\web\Response
{
    use Dd;
    use SnapshotAssertions;

    public function behaviors(): array
    {
        return [
            ExpectableBehavior::class,
            TestableResponseBehavior::class
        ];
    }

    public function send()
    {
        // This page intentionally left blank so we can inspect the response body without it
        // being prematurely written to the screen
    }

    /**
     * Make prepare() publicly accessible
     */
    public function prepare(): void
    {
        parent::prepare();
    }

    public function toString()
    {
        return $this->content;
    }
}

<?php

namespace markhuot\craftpest\test;

trait SnapshotAssertions
{
    public function assertMatchesSnapshot()
    {
        expect($this)->toMatchSnapshot();
    }
}

<?php

namespace markhuot\craftpest\behaviors;

use Pest\Expectation;
use yii\base\Behavior;

class ExpectableBehavior extends Behavior
{
    function expect()
    {
        return new Expectation($this->owner);
    }
}

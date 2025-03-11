<?php

namespace markhuot\craftpest\behaviors;

use Pest\Expectation;
use yii\base\Behavior;

class ExpectableBehavior extends Behavior
{
    public function expect(): \Pest\Expectation
    {
        return new Expectation($this->owner);
    }
}

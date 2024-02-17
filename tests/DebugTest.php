<?php

use function markhuot\craftpest\helpers\test\dd;

it('debugs', function () {
    dd(\Craft::$app->version);
});

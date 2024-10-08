<?php

use markhuot\craftpest\console\PestController;

it('executes console commands')
    ->console(PestController::class, 'internal')
    ->assertSee('stdout')
    ->assertSee('stderr')
    ->assertDontSee('missing');

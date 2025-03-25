<?php

use markhuot\craftpest\console\PestController;

it('executes console commands')
    ->console(PestController::class, 'internal')
    ->assertSuccesful()
    ->assertSee('stdout')
    ->assertSee('stderr')
    ->assertDontSee('missing')
    ->skip();

it('gets stdout and stderr', function () {
    $response = $this->console(PestController::class, 'internal');

    expect($response->exitCode)->toBe(0);
    expect($response->stdout)->toContain('stdout');
    expect($response->stderr)->toContain('stderr');
})->skip();

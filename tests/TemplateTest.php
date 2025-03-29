<?php

it('renders templates', function () {
    $this->expectTemplate('variable', ['foo' => 'bar'])->toBe('bar'.PHP_EOL);
});

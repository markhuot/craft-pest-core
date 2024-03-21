<?php

namespace markhuot\craftpest\interfaces;

interface RenderCompiledClassesInterface
{
    public function handle(bool $forceRecreate = false);
}

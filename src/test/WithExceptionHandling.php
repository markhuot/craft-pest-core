<?php

namespace markhuot\craftpest\test;

trait WithExceptionHandling
{
    protected bool $withExceptionHandling = true;

    public function withExceptionHandling(): self
    {
        $this->withExceptionHandling = true;

        return $this;
    }

    public function withoutExceptionHandling(): self
    {
        $this->withExceptionHandling = false;

        return $this;
    }

    public function shouldRenderExceptionsAsHtml(): bool
    {
        return $this->withExceptionHandling;
    }
}

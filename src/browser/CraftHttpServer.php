<?php

namespace markhuot\craftpest\browser;

use craft\helpers\UrlHelper;
use Throwable;

class CraftHttpServer implements \Pest\Browser\Contracts\HttpServer
{
    protected function getBasePath(): string
    {
        return \Craft::getAlias('@webroot');
    }

    public function start(): void
    {
        // TODO: Implement start() method.
    }

    public function stop(): void
    {
        // TODO: Implement stop() method.
    }

    public function rewrite(string $url): string
    {
        return UrlHelper::url($url);
    }

    public function flush(): void
    {
        // TODO: Implement flush() method.
    }

    public function bootstrap(): void
    {
        // Nothing to do here, Craft is already bootstrapped by the Pest / PHPUnit parent process
    }

    public function lastThrowable(): ?Throwable
    {
        // TODO: Implement lastThrowable() method.
    }

    public function throwLastThrowableIfNeeded(): void
    {
        // TODO: Implement throwLastThrowableIfNeeded() method.
    }
}

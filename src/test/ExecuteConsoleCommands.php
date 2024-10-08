<?php

namespace markhuot\craftpest\test;

use markhuot\craftpest\console\TestableResponse;

trait ExecuteConsoleCommands
{
    protected $stdout = '';

    protected $stderr = '';

    public function setupExecuteConsoleCommands(): void
    {
        stream_filter_register('craftpest.buffer.stdout', 'markhuot\\craftpest\\io\\Buffer');
        stream_filter_register('craftpest.buffer.stderr', 'markhuot\\craftpest\\io\\Buffer');
    }

    public function storeStdOut(string $out): void
    {
        $this->stdout .= $out;
    }

    public function storeStdErr(string $err): void
    {
        $this->stderr .= $err;
    }

    public function console(string $className, string $action, array $params = []): TestableResponse
    {
        $stdout = stream_filter_append(\STDOUT, 'craftpest.buffer.stdout');
        $stderr = stream_filter_append(\STDERR, 'craftpest.buffer.stderr');

        $controller = new $className('id', \Craft::$app);
        $exitCode = call_user_func_array([$controller, 'action'.ucfirst($action)], $params);

        stream_filter_remove($stdout);
        stream_filter_remove($stderr);

        return new TestableResponse($exitCode, $this->stdout, $this->stderr);
    }
}

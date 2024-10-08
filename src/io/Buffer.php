<?php

namespace markhuot\craftpest\io;

use function markhuot\craftpest\helpers\test\test;

class Buffer extends \php_user_filter
{
    protected string $streamName = 'stdout';

    public function onCreate(): bool
    {
        $this->streamName = match ($this->filtername) {
            'craftpest.buffer.stdout' => 'stdout',
            'craftpest.buffer.stderr' => 'stderr',
            default => throw new \RuntimeException('Unknown stream'),
        };

        return true;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            if ($this->streamName === 'stdout') {
                test()->storeStdOut($bucket->data);
            } else {
                test()->storeStdErr($bucket->data);
            }

            $bucket->data = '';
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}

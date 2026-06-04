<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Support;

use function is_resource;
use function proc_close;
use function proc_open;

use const STDERR;
use const STDIN;
use const STDOUT;

final class ProcessRunner
{
    /** @param list<string> $command */
    public function run(array $command, ?string $cwd = null): int
    {
        $process = proc_open($command, [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ], $pipes, $cwd);

        if (!is_resource($process)) {
            return 1;
        }

        return proc_close($process);
    }
}

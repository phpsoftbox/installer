<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Support;

interface ProcessRunnerInterface
{
    /**
     * Запускает процесс без shell (аргументы передаются как есть) и возвращает код завершения.
     *
     * @param list<string> $command
     */
    public function run(array $command, ?string $cwd = null): int;
}

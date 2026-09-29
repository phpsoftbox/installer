<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Support;

use PhpSoftBox\Installer\Support\ProcessRunnerInterface;

/**
 * Запоминает команды вместо запуска процессов.
 */
final class RecordingProcessRunner implements ProcessRunnerInterface
{
    /**
     * @var list<array{command: list<string>, cwd: ?string}>
     */
    public array $calls = [];

    public function __construct(
        private readonly int $exitCode = 0,
    ) {
    }

    public function run(array $command, ?string $cwd = null): int
    {
        $this->calls[] = ['command' => $command, 'cwd' => $cwd];

        return $this->exitCode;
    }
}

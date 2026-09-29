<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Command\HandlerInterface;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\ProcessRunner;
use PhpSoftBox\Installer\Support\ProcessRunnerInterface;

final class SelfUpdateHandler implements HandlerInterface
{
    public function __construct(
        private readonly ProcessRunnerInterface $processRunner = new ProcessRunner(),
    ) {
    }

    public function run(RunnerInterface $runner): int|Response
    {
        $runner->io()->writeln('Updating PhpSoftBox installer...', 'comment');

        return $this->processRunner->run([
            'composer',
            'global',
            'require',
            'phpsoftbox/installer:^1.0',
            '--with-all-dependencies',
        ]);
    }
}

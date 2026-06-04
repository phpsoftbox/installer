<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Command\HandlerInterface;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\ProcessRunner;

final class SelfUpdateHandler implements HandlerInterface
{
    public function run(RunnerInterface $runner): int|Response
    {
        $runner->io()->writeln('Updating PhpSoftBox installer...', 'comment');

        return new ProcessRunner()->run([
            'composer',
            'global',
            'require',
            'phpsoftbox/installer:dev-master',
            'phpsoftbox/cli-app:dev-master',
            'phpsoftbox/error-formatter:dev-master',
            '--prefer-stable',
        ]);
    }
}

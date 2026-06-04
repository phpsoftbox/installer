<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Command\HandlerInterface;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\WorkspaceContext;
use PhpSoftBox\Installer\Support\Filesystem;

use function copy;
use function file_put_contents;
use function is_file;

final class WorkspaceInitHandler implements HandlerInterface
{
    public function run(RunnerInterface $runner): int|Response
    {
        $io = $runner->io();
        if (!WorkspaceContext::assertInstallRoot($io)) {
            return Response::FAILURE;
        }

        $force = (bool) $runner->request()->option('force', false);
        foreach (['.env' => '.env.example', '.workspace.ini' => '.workspace.ini.example'] as $target => $source) {
            if (is_file($target) && !$force) {
                $io->writeln('Skip existing ' . $target, 'comment');
                continue;
            }
            if (!copy($source, $target)) {
                $io->writeln('Cannot create ' . $target, 'error');

                return Response::FAILURE;
            }
            $io->writeln('Created ' . $target, 'success');
        }

        $filesystem = new Filesystem();
        $filesystem->ensureDirectory('local');
        file_put_contents('local/.gitkeep', '');

        return Response::SUCCESS;
    }
}

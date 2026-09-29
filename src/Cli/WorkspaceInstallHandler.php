<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Command\HandlerInterface;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\Filesystem;
use PhpSoftBox\Installer\Support\ProcessRunner;
use PhpSoftBox\Installer\Support\ProcessRunnerInterface;
use RuntimeException;

use function file_put_contents;
use function is_dir;
use function trim;

final class WorkspaceInstallHandler implements HandlerInterface
{
    public const DEFAULT_SOURCE = 'https://github.com/phpsoftbox/workspace.git';

    public function __construct(
        private readonly ProcessRunnerInterface $processRunner = new ProcessRunner(),
    ) {
    }

    public function run(RunnerInterface $runner): int|Response
    {
        $request = $runner->request();
        $io      = $runner->io();

        $dir    = trim((string) $request->param('dir', 'Workspace'));
        $source = trim((string) $request->option('source', self::DEFAULT_SOURCE));
        $branch = trim((string) $request->option('branch', ''));
        $force  = (bool) $request->option('force', false);

        if ($dir === '') {
            $io->writeln('Target directory is required.', 'error');

            return Response::INVALID_INPUT;
        }

        $fs = new Filesystem();
        try {
            if (is_dir($dir)) {
                if (!$force) {
                    $io->writeln('Target directory already exists: ' . $dir, 'error');

                    return Response::FAILURE;
                }
                $fs->remove($dir);
            }

            if ($fs->isLocalDirectory($source)) {
                $fs->copyDirectory($source, $dir, ['.git', '.env', '.workspace.ini', 'local']);
            } else {
                $command = ['git', 'clone', '--depth=1'];
                if ($branch !== '') {
                    $command[] = '--branch';
                    $command[] = $branch;
                }
                // `--` не даёт источнику вида `--upload-pack=...` стать опцией git.
                $command[] = '--';
                $command[] = $source;
                $command[] = $dir;

                $code = $this->processRunner->run($command);
                if ($code !== 0) {
                    return $code;
                }
                $fs->remove($dir . '/.git');
            }

            $fs->ensureDirectory($dir . '/local');
            file_put_contents($dir . '/local/.gitkeep', '');
        } catch (RuntimeException $exception) {
            $io->writeln($exception->getMessage(), 'error');

            return Response::FAILURE;
        }

        $io->writeln('Workspace installed: ' . $dir, 'success');
        $io->writeln('Next: cd ' . $dir . ' && phpsoftbox workspace:init', 'comment');

        return Response::SUCCESS;
    }
}

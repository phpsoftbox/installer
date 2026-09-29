<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Command\HandlerInterface;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\EnvFile;
use PhpSoftBox\Installer\Support\Filesystem;
use PhpSoftBox\Installer\Support\ProcessRunner;
use PhpSoftBox\Installer\Support\ProcessRunnerInterface;
use PhpSoftBox\Installer\Support\WorkspaceContext;
use RuntimeException;

use function is_dir;
use function trim;

final class NewProjectHandler implements HandlerInterface
{
    public const string DEFAULT_SOURCE = 'https://github.com/phpsoftbox/app-backend.git';

    public function __construct(
        private readonly ProcessRunnerInterface $processRunner = new ProcessRunner(),
    ) {
    }

    public function run(RunnerInterface $runner): int|Response
    {
        $io = $runner->io();
        if (!WorkspaceContext::assertRoot($io)) {
            return Response::FAILURE;
        }

        $request = $runner->request();
        $service = trim((string) $request->param('service', ''));
        $source  = trim((string) $request->option('source', self::DEFAULT_SOURCE));
        $branch  = trim((string) $request->option('branch', ''));
        $force   = (bool) $request->option('force', false);

        $fs = new Filesystem();

        try {
            $fs->assertSafeServiceName($service);
            $fs->ensureDirectory('local');

            $target = 'local/' . $service;
            if (is_dir($target)) {
                if (!$force) {
                    $io->writeln('Service directory already exists: ' . $target, 'error');

                    return Response::FAILURE;
                }
                $fs->remove($target);
            }

            if ($fs->isLocalDirectory($source)) {
                $fs->copyDirectory($source, $target, ['.git', 'vendor', 'node_modules', 'public/build', 'local', '.env']);
            } else {
                $command = ['git', 'clone', '--depth=1'];
                if ($branch !== '') {
                    $command[] = '--branch';
                    $command[] = $branch;
                }
                // `--` не даёт источнику вида `--upload-pack=...` стать опцией git.
                $command[] = '--';
                $command[] = $source;
                $command[] = $target;

                $code = $this->processRunner->run($command);
                if ($code !== 0) {
                    return $code;
                }
            }

            $fs->remove($target . '/.git');

            $env = new EnvFile();

            $env->set('.env', 'BACKEND_PATH', './local/' . $service);
            $env->set('.env', 'PHP_IDE_CONFIG', 'serverName=' . $service);
        } catch (RuntimeException $exception) {
            $io->writeln($exception->getMessage(), 'error');

            return Response::FAILURE;
        }

        $io->writeln('Service created: local/' . $service, 'success');

        return Response::SUCCESS;
    }
}

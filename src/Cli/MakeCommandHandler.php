<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\ProcessRunner;
use PhpSoftBox\Installer\Support\ProfileConfig;
use PhpSoftBox\Installer\Support\WorkspaceContext;

use function array_unshift;
use function implode;
use function in_array;
use function is_string;
use function trim;

final class MakeCommandHandler
{
    private const PROFILED_DOWN_TARGETS = ['down', 'down-clear'];

    /** @return array<string, array{method:string,target:string}> */
    public static function commands(): array
    {
        return [
            'init'             => ['method' => 'init', 'target' => 'init'],
            'config'           => ['method' => 'config', 'target' => 'config'],
            'up'               => ['method' => 'up', 'target' => 'up'],
            'down'             => ['method' => 'down', 'target' => 'down'],
            'down-clear'       => ['method' => 'downClear', 'target' => 'down-clear'],
            'build'            => ['method' => 'build', 'target' => 'build'],
            'build-no-cache'   => ['method' => 'buildNoCache', 'target' => 'build-no-cache'],
            'ps'               => ['method' => 'ps', 'target' => 'ps'],
            'logs'             => ['method' => 'logs', 'target' => 'logs'],
            'shell'            => ['method' => 'shell', 'target' => 'php-shell'],
            'composer-install' => ['method' => 'composerInstall', 'target' => 'composer-install'],
            'composer-update'  => ['method' => 'composerUpdate', 'target' => 'composer-update'],
            'test'             => ['method' => 'test', 'target' => 'test'],
            'cs-check'         => ['method' => 'csCheck', 'target' => 'cs-check'],
            'cs-fix'           => ['method' => 'csFix', 'target' => 'cs-fix'],
            'yarn-install'     => ['method' => 'yarnInstall', 'target' => 'yarn-install'],
            'yarn-build'       => ['method' => 'yarnBuild', 'target' => 'yarn-build'],
            'vite-dev'         => ['method' => 'viteDev', 'target' => 'vite-dev'],
        ];
    }

    public function config(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'config');
    }

    public function init(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'init');
    }

    public function up(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'up');
    }

    public function down(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'down');
    }

    public function downClear(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'down-clear');
    }

    public function build(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'build');
    }

    public function buildNoCache(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'build-no-cache');
    }

    public function ps(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'ps');
    }

    public function logs(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'logs');
    }

    public function shell(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'php-shell');
    }

    public function composerInstall(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'composer-install');
    }

    public function composerUpdate(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'composer-update');
    }

    public function test(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'test');
    }

    public function csCheck(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'cs-check');
    }

    public function csFix(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'cs-fix');
    }

    public function yarnInstall(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'yarn-install');
    }

    public function yarnBuild(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'yarn-build');
    }

    public function viteDev(RunnerInterface $runner): int|Response
    {
        return $this->runMake($runner, 'vite-dev');
    }

    private function runMake(RunnerInterface $runner, string $target): int|Response
    {
        if (!WorkspaceContext::assertRoot($runner->io())) {
            return Response::FAILURE;
        }

        $command = ['make', $target];
        $profiles = $this->requestedProfiles($runner);
        if (is_string($profiles) && trim($profiles) !== '') {
            $profileList = (new ProfileConfig())->normalize([$profiles]);
            $profiles    = implode(' ', $profileList);
            $command[]   = 'PROFILES=' . $profiles;
        } else {
            $profileList = (new ProfileConfig())->read(WorkspaceContext::root() . '/.workspace.ini');
        }

        if (in_array($target, self::PROFILED_DOWN_TARGETS, true) && $profileList !== []) {
            array_unshift($command, 'env', 'COMPOSE_PROFILES=' . implode(',', $profileList));
        }

        return (new ProcessRunner())->run($command, WorkspaceContext::root());
    }

    private function requestedProfiles(RunnerInterface $runner): ?string
    {
        $profiles = $runner->request()->option('profiles');

        return is_string($profiles) && trim($profiles) !== '' ? trim($profiles) : null;
    }
}

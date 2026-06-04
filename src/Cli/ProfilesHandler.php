<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\WorkspaceContext;
use PhpSoftBox\Installer\Support\ProfileConfig;

use function array_filter;
use function array_values;
use function implode;
use function in_array;
use function is_array;

final class ProfilesHandler
{
    private const CONFIG = '.workspace.ini';

    public function list(RunnerInterface $runner): int|Response
    {
        if (!WorkspaceContext::assertRoot($runner->io())) {
            return Response::FAILURE;
        }

        $profiles = (new ProfileConfig())->read(self::CONFIG);
        $runner->io()->writeln($profiles === [] ? 'No profiles configured.' : implode(' ', $profiles));

        return Response::SUCCESS;
    }

    public function set(RunnerInterface $runner): int|Response
    {
        if (!WorkspaceContext::assertRoot($runner->io())) {
            return Response::FAILURE;
        }

        $config   = new ProfileConfig();
        $profiles = $this->requestedProfiles($runner, $config);
        if ($profiles === []) {
            $runner->io()->writeln('At least one profile is required.', 'error');

            return Response::INVALID_INPUT;
        }

        $config->write(self::CONFIG, $profiles);
        $runner->io()->writeln('Profiles: ' . implode(' ', $profiles), 'success');

        return Response::SUCCESS;
    }

    public function add(RunnerInterface $runner): int|Response
    {
        if (!WorkspaceContext::assertRoot($runner->io())) {
            return Response::FAILURE;
        }

        $config   = new ProfileConfig();
        $profiles = $config->read(self::CONFIG);
        foreach ($this->requestedProfiles($runner, $config) as $profile) {
            if (!in_array($profile, $profiles, true)) {
                $profiles[] = $profile;
            }
        }

        $config->write(self::CONFIG, $profiles);
        $runner->io()->writeln('Profiles: ' . implode(' ', $profiles), 'success');

        return Response::SUCCESS;
    }

    public function remove(RunnerInterface $runner): int|Response
    {
        if (!WorkspaceContext::assertRoot($runner->io())) {
            return Response::FAILURE;
        }

        $config = new ProfileConfig();
        $remove = $this->requestedProfiles($runner, $config);
        $profiles = array_values(array_filter(
            $config->read(self::CONFIG),
            static fn (string $profile): bool => !in_array($profile, $remove, true),
        ));

        $config->write(self::CONFIG, $profiles);
        $runner->io()->writeln('Profiles: ' . implode(' ', $profiles), 'success');

        return Response::SUCCESS;
    }

    /** @return list<string> */
    private function requestedProfiles(RunnerInterface $runner, ProfileConfig $config): array
    {
        $values = $runner->request()->param('profiles', []);
        if (!is_array($values)) {
            $values = [(string) $values];
        }

        return $config->normalize($values);
    }
}

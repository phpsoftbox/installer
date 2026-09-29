<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use Closure;
use InvalidArgumentException;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\CliApp\Runner\RunnerInterface;
use PhpSoftBox\Installer\Support\ProfileConfig;
use PhpSoftBox\Installer\Support\WorkspaceContext;

use function array_filter;
use function array_values;
use function implode;
use function in_array;
use function is_array;

final class ProfilesHandler
{
    private const string CONFIG = '.workspace.ini';

    public function list(RunnerInterface $runner): int|Response
    {
        return $this->guarded($runner, static function (ProfileConfig $config) use ($runner): int {
            $profiles = $config->read(self::CONFIG);
            $runner->io()->writeln($profiles === [] ? 'No profiles configured.' : implode(' ', $profiles));

            return Response::SUCCESS;
        });
    }

    public function set(RunnerInterface $runner): int|Response
    {
        return $this->guarded($runner, function (ProfileConfig $config) use ($runner): int {
            $profiles = $this->requestedProfiles($runner, $config);
            if ($profiles === []) {
                $runner->io()->writeln('At least one profile is required.', 'error');

                return Response::INVALID_INPUT;
            }

            $config->write(self::CONFIG, $profiles);
            $runner->io()->writeln('Profiles: ' . implode(' ', $profiles), 'success');

            return Response::SUCCESS;
        });
    }

    public function add(RunnerInterface $runner): int|Response
    {
        return $this->guarded($runner, function (ProfileConfig $config) use ($runner): int {
            $profiles = $config->read(self::CONFIG);
            foreach ($this->requestedProfiles($runner, $config) as $profile) {
                if (!in_array($profile, $profiles, true)) {
                    $profiles[] = $profile;
                }
            }

            $config->write(self::CONFIG, $profiles);
            $runner->io()->writeln('Profiles: ' . implode(' ', $profiles), 'success');

            return Response::SUCCESS;
        });
    }

    public function remove(RunnerInterface $runner): int|Response
    {
        return $this->guarded($runner, function (ProfileConfig $config) use ($runner): int {
            $remove   = $this->requestedProfiles($runner, $config);
            $profiles = array_values(array_filter(
                $config->read(self::CONFIG),
                static fn (string $profile): bool => !in_array($profile, $remove, true),
            ));

            $config->write(self::CONFIG, $profiles);
            $runner->io()->writeln('Profiles: ' . implode(' ', $profiles), 'success');

            return Response::SUCCESS;
        });
    }

    /**
     * Проверяет корень Workspace и превращает недопустимое имя профиля в ошибку ввода.
     *
     * @param Closure(ProfileConfig): int $action
     */
    private function guarded(RunnerInterface $runner, Closure $action): int
    {
        if (!WorkspaceContext::assertRoot($runner->io())) {
            return Response::FAILURE;
        }

        try {
            return $action(new ProfileConfig());
        } catch (InvalidArgumentException $exception) {
            $runner->io()->writeln($exception->getMessage(), 'error');

            return Response::INVALID_INPUT;
        }
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

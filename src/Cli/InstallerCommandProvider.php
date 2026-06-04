<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Cli;

use PhpSoftBox\CliApp\Command\Command;
use PhpSoftBox\CliApp\Command\CommandRegistryInterface;
use PhpSoftBox\CliApp\Loader\CommandProviderInterface;

use function PhpSoftBox\CliApp\arg;
use function PhpSoftBox\CliApp\flag;
use function PhpSoftBox\CliApp\opt;

final class InstallerCommandProvider implements CommandProviderInterface
{
    public function register(CommandRegistryInterface $registry): void
    {
        $registry->register(Command::define(
            name: 'workspace:install',
            description: 'Install Workspace into a target directory',
            signature: [
                arg('dir', 'Target directory', required: false, default: 'Workspace'),
                opt('source', 's', 'Git URL or local Workspace directory', required: false, default: WorkspaceInstallHandler::DEFAULT_SOURCE),
                opt('branch', 'b', 'Git branch/tag for remote source', required: false),
                flag('force', 'f', 'Remove target directory before install'),
            ],
            handler: WorkspaceInstallHandler::class,
        ));

        $registry->register(Command::define(
            name: 'workspace:init',
            description: 'Create local Workspace config files from examples',
            signature: [
                flag('force', 'f', 'Overwrite existing .env and .workspace.ini'),
            ],
            handler: WorkspaceInitHandler::class,
        ));

        $registry->register(Command::define(
            name: 'new',
            description: 'Create a new AppBackend service inside local/<service>',
            signature: [
                arg('service', 'Service directory name inside local'),
                opt('source', 's', 'Git URL or local AppBackend skeleton directory', required: false, default: NewProjectHandler::DEFAULT_SOURCE),
                opt('branch', 'b', 'Git branch/tag for remote source', required: false),
                flag('force', 'f', 'Remove target service directory before create'),
            ],
            handler: NewProjectHandler::class,
        ));

        $registry->register(Command::define(
            name: 'self-update',
            description: 'Update PhpSoftBox installer global Composer package',
            signature: [],
            handler: SelfUpdateHandler::class,
        ));

        foreach (MakeCommandHandler::commands() as $name => $definition) {
            $registry->register(Command::define(
                name: $name,
                description: 'Run make ' . $definition['target'] . ' in the current Workspace root',
                signature: [
                    opt('profiles', 'p', 'Override Workspace profiles for this run', required: false),
                ],
                handler: [MakeCommandHandler::class, $definition['method']],
            ));
        }

        $registry->register(Command::define(
            name: 'profiles:list',
            description: 'Show default Workspace profiles',
            signature: [],
            handler: [ProfilesHandler::class, 'list'],
        ));
        $registry->register(Command::define(
            name: 'profiles:set',
            description: 'Replace default Workspace profiles',
            signature: [
                arg('profiles', 'Profiles separated by spaces or commas', variadic: true),
            ],
            handler: [ProfilesHandler::class, 'set'],
        ));
        $registry->register(Command::define(
            name: 'profiles:add',
            description: 'Add profiles to the default Workspace profile set',
            signature: [
                arg('profiles', 'Profiles separated by spaces or commas', variadic: true),
            ],
            handler: [ProfilesHandler::class, 'add'],
        ));
        $registry->register(Command::define(
            name: 'profiles:remove',
            description: 'Remove profiles from the default Workspace profile set',
            signature: [
                arg('profiles', 'Profiles separated by spaces or commas', variadic: true),
            ],
            handler: [ProfilesHandler::class, 'remove'],
        ));
    }
}

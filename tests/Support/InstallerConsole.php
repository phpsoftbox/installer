<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Support;

use PhpSoftBox\CliApp\CliApp;
use PhpSoftBox\CliApp\Command\CommandDefinition;
use PhpSoftBox\CliApp\Command\InMemoryCommandRegistry;
use PhpSoftBox\CliApp\Io\NullIo;
use PhpSoftBox\CliApp\Response;
use PhpSoftBox\Installer\Cli\InstallerCommandProvider;
use PhpSoftBox\Installer\Support\ProcessRunnerInterface;
use ReflectionClass;

use function is_array;
use function is_string;

/**
 * Запускает команды installer через CliApp, подставляя в обработчики тестовый ProcessRunner.
 */
final class InstallerConsole
{
    public function __construct(
        private readonly ProcessRunnerInterface $processRunner,
    ) {
    }

    /**
     * @param list<string> $argv
     */
    public function run(string $command, array $argv = []): Response
    {
        $definitions = new InMemoryCommandRegistry(false);

        new InstallerCommandProvider()->register($definitions);

        $registry = new InMemoryCommandRegistry(false);

        foreach ($definitions->all() as $definition) {
            $registry->register(new CommandDefinition(
                $definition->name,
                $definition->description,
                $definition->signature,
                $this->handler($definition->handler),
                $definition->aliases,
                $definition->meta,
                $definition->environments,
                $definition->asDaemon,
            ));
        }

        return new CliApp($registry, new NullIo())->runCommand($command, $argv);
    }

    private function handler(mixed $handler): mixed
    {
        if (is_string($handler)) {
            return $this->instantiate($handler);
        }

        if (is_array($handler) && is_string($handler[0] ?? null)) {
            $handler[0] = $this->instantiate($handler[0]);
        }

        return $handler;
    }

    /**
     * @param class-string $class
     */
    private function instantiate(string $class): object
    {
        $constructor = new ReflectionClass($class)->getConstructor();

        return $constructor !== null && $constructor->getNumberOfParameters() > 0
            ? new $class($this->processRunner)
            : new $class();
    }
}

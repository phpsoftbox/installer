<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Cli;

use PhpSoftBox\CliApp\Response;
use PhpSoftBox\Installer\Cli\MakeCommandHandler;
use PhpSoftBox\Installer\Tests\Support\InstallerConsole;
use PhpSoftBox\Installer\Tests\Support\RecordingProcessRunner;
use PhpSoftBox\Installer\Tests\Support\WorkspaceDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getcwd;

#[CoversClass(MakeCommandHandler::class)]
#[CoversMethod(MakeCommandHandler::class, 'up')]
#[CoversMethod(MakeCommandHandler::class, 'down')]
final class MakeCommandHandlerTest extends TestCase
{
    private WorkspaceDirectory $workspace;

    protected function setUp(): void
    {
        $this->workspace = new WorkspaceDirectory('php-cli mysql');
    }

    protected function tearDown(): void
    {
        $this->workspace->cleanup();
    }

    /**
     * Проверяем, что --profiles передаётся в make как PROFILES, а make запускается в корне Workspace.
     *
     * @see MakeCommandHandler::up()
     */
    #[Test]
    public function upPassesOverriddenProfilesToMake(): void
    {
        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('up', ['--profiles=mongo,redis']);

        $this->assertSame(Response::SUCCESS, $response->code);
        $this->assertSame(['make', 'up', 'PROFILES=mongo redis'], $runner->calls[0]['command']);
        $this->assertSame(getcwd(), $runner->calls[0]['cwd']);
    }

    /**
     * Проверяем, что down получает COMPOSE_PROFILES из .workspace.ini, чтобы остановить все профили.
     *
     * @see MakeCommandHandler::down()
     */
    #[Test]
    public function downUsesDefaultProfilesFromConfig(): void
    {
        $runner = new RecordingProcessRunner();

        new InstallerConsole($runner)->run('down');

        $this->assertSame(['env', 'COMPOSE_PROFILES=php-cli,mysql', 'make', 'down'], $runner->calls[0]['command']);
    }

    /**
     * Проверяем, что профиль с make-выражением отклоняется и make не запускается.
     *
     * @see MakeCommandHandler::up()
     */
    #[Test]
    public function upRejectsUnsafeProfile(): void
    {
        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('up', ['--profiles=$(shell id)']);

        $this->assertSame(Response::INVALID_INPUT, $response->code);
        $this->assertSame([], $runner->calls);
    }
}

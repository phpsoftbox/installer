<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Cli;

use PhpSoftBox\CliApp\Response;
use PhpSoftBox\Installer\Cli\WorkspaceInstallHandler;
use PhpSoftBox\Installer\Tests\Support\InstallerConsole;
use PhpSoftBox\Installer\Tests\Support\RecordingProcessRunner;
use PhpSoftBox\Installer\Tests\Support\WorkspaceDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function mkdir;

#[CoversClass(WorkspaceInstallHandler::class)]
#[CoversMethod(WorkspaceInstallHandler::class, 'run')]
final class WorkspaceInstallHandlerTest extends TestCase
{
    private WorkspaceDirectory $workspace;

    protected function setUp(): void
    {
        $this->workspace = new WorkspaceDirectory();
    }

    protected function tearDown(): void
    {
        $this->workspace->cleanup();
    }

    /**
     * Проверяем, что Workspace клонируется с `--` перед источником и каталогом и получает local/.
     *
     * @see WorkspaceInstallHandler::run()
     */
    #[Test]
    public function clonesRemoteSourceWithEndOfOptions(): void
    {
        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('workspace:install', ['ws', '--source=https://example.com/ws.git']);

        $this->assertSame(Response::SUCCESS, $response->code);
        $this->assertSame(
            ['git', 'clone', '--depth=1', '--', 'https://example.com/ws.git', 'ws'],
            $runner->calls[0]['command'],
        );
        $this->assertFileExists('ws/local/.gitkeep');
    }

    /**
     * Проверяем, что существующий каталог без --force не удаляется и не перезаписывается.
     *
     * @see WorkspaceInstallHandler::run()
     */
    #[Test]
    public function keepsExistingDirectoryWithoutForce(): void
    {
        mkdir('ws');
        file_put_contents('ws/keep.txt', 'keep');
        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('workspace:install', ['ws']);

        $this->assertSame(Response::FAILURE, $response->code);
        $this->assertSame([], $runner->calls);
        $this->assertFileExists('ws/keep.txt');
    }
}

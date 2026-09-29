<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Cli;

use PhpSoftBox\CliApp\Response;
use PhpSoftBox\Installer\Cli\NewProjectHandler;
use PhpSoftBox\Installer\Tests\Support\InstallerConsole;
use PhpSoftBox\Installer\Tests\Support\RecordingProcessRunner;
use PhpSoftBox\Installer\Tests\Support\WorkspaceDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function file_put_contents;
use function mkdir;

#[CoversClass(NewProjectHandler::class)]
#[CoversMethod(NewProjectHandler::class, 'run')]
final class NewProjectHandlerTest extends TestCase
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
     * Проверяем, что удалённый источник клонируется с `--` перед позиционными аргументами и .env указывает на сервис.
     *
     * @see NewProjectHandler::run()
     */
    #[Test]
    public function clonesRemoteSourceWithEndOfOptions(): void
    {
        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('new', ['api', '--source=--upload-pack=touch /tmp/pwn', '--branch=v1']);

        // Источник вида `--upload-pack=...` стоит после `--` и не станет опцией git.
        $this->assertSame(Response::SUCCESS, $response->code);
        $this->assertSame(
            ['git', 'clone', '--depth=1', '--branch', 'v1', '--', '--upload-pack=touch /tmp/pwn', 'local/api'],
            $runner->calls[0]['command'],
        );
        $this->assertStringContainsString('BACKEND_PATH=./local/api', (string) file_get_contents('.env'));
    }

    /**
     * Проверяем, что локальный источник копируется без .git и .env.
     *
     * @see NewProjectHandler::run()
     */
    #[Test]
    public function copiesLocalSourceWithoutSecrets(): void
    {
        mkdir($this->workspace->path . '/skeleton/.git', 0775, true);
        file_put_contents($this->workspace->path . '/skeleton/composer.json', '{}');
        file_put_contents($this->workspace->path . '/skeleton/.env', 'APP_KEY=secret');

        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('new', ['api', '--source=./skeleton']);

        $this->assertSame(Response::SUCCESS, $response->code);
        $this->assertSame([], $runner->calls);
        $this->assertFileExists('local/api/composer.json');
        $this->assertFileDoesNotExist('local/api/.env');
        $this->assertDirectoryDoesNotExist('local/api/.git');
    }

    /**
     * Проверяем, что имя сервиса с выходом из local/ отклоняется до любых действий.
     *
     * @see NewProjectHandler::run()
     */
    #[Test]
    public function rejectsServiceNameOutsideLocal(): void
    {
        $runner = new RecordingProcessRunner();

        $response = new InstallerConsole($runner)->run('new', ['../escape']);

        $this->assertSame(Response::FAILURE, $response->code);
        $this->assertSame([], $runner->calls);
    }
}

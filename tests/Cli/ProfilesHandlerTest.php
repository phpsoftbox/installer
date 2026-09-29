<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Cli;

use PhpSoftBox\CliApp\Response;
use PhpSoftBox\Installer\Cli\ProfilesHandler;
use PhpSoftBox\Installer\Support\ProfileConfig;
use PhpSoftBox\Installer\Tests\Support\InstallerConsole;
use PhpSoftBox\Installer\Tests\Support\RecordingProcessRunner;
use PhpSoftBox\Installer\Tests\Support\WorkspaceDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfilesHandler::class)]
#[CoversMethod(ProfilesHandler::class, 'add')]
#[CoversMethod(ProfilesHandler::class, 'remove')]
#[CoversMethod(ProfilesHandler::class, 'set')]
final class ProfilesHandlerTest extends TestCase
{
    private WorkspaceDirectory $workspace;

    protected function setUp(): void
    {
        $this->workspace = new WorkspaceDirectory('php-cli');
    }

    protected function tearDown(): void
    {
        $this->workspace->cleanup();
    }

    /**
     * Проверяем, что add добавляет профили без дублей, а remove их убирает.
     *
     * @see ProfilesHandler::add()
     * @see ProfilesHandler::remove()
     */
    #[Test]
    public function addAndRemoveUpdateDefaultProfiles(): void
    {
        $console = new InstallerConsole(new RecordingProcessRunner());

        $console->run('profiles:add', ['mysql', 'php-cli,redis']);
        $this->assertSame(['php-cli', 'mysql', 'redis'], new ProfileConfig()->read('.workspace.ini'));

        $console->run('profiles:remove', ['mysql']);
        $this->assertSame(['php-cli', 'redis'], new ProfileConfig()->read('.workspace.ini'));
    }

    /**
     * Проверяем, что set с недопустимым именем профиля возвращает ошибку ввода и не меняет конфиг.
     *
     * @see ProfilesHandler::set()
     */
    #[Test]
    public function setRejectsUnsafeProfile(): void
    {
        $response = new InstallerConsole(new RecordingProcessRunner())->run('profiles:set', ['php-cli;id']);

        $this->assertSame(Response::INVALID_INPUT, $response->code);
        $this->assertSame(['php-cli'], new ProfileConfig()->read('.workspace.ini'));
    }
}

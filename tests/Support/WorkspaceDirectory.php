<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Support;

use PhpSoftBox\Installer\Support\Filesystem;
use RuntimeException;

use function chdir;
use function file_put_contents;
use function getcwd;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

/**
 * Временный корень Workspace: создаёт обязательные файлы и делает каталог текущим.
 */
final class WorkspaceDirectory
{
    private string $previousCwd;

    public readonly string $path;

    public function __construct(string $profiles = 'php-cli')
    {
        $this->path = sys_get_temp_dir() . '/psb-installer-' . uniqid('', true);
        mkdir($this->path . '/local', 0775, true);

        file_put_contents($this->path . '/compose.yml', "services: {}\n");
        file_put_contents($this->path . '/Makefile', "up:\n");
        file_put_contents($this->path . '/.env', "APP_ENV=dev\n");
        file_put_contents($this->path . '/.workspace.ini', "[profiles]\ndefault_profiles = {$profiles}\n");

        $cwd = getcwd();
        if ($cwd === false) {
            throw new RuntimeException('Cannot detect current directory.');
        }

        $this->previousCwd = $cwd;
        chdir($this->path);
    }

    public function cleanup(): void
    {
        chdir($this->previousCwd);
        new Filesystem()->remove($this->path);
    }
}

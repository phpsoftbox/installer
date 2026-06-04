<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Support;

use PhpSoftBox\CliApp\Io\IoInterface;
use PhpSoftBox\CliApp\Response;

use function getcwd;
use function is_dir;
use function is_file;

final class WorkspaceContext
{
    public static function root(): string
    {
        $cwd = getcwd();

        return $cwd === false ? '.' : $cwd;
    }

    public static function assertRoot(IoInterface $io): bool
    {
        $root = self::root();
        foreach (['compose.yml', 'Makefile', '.env', '.workspace.ini'] as $file) {
            if (!is_file($root . '/' . $file)) {
                $io->writeln('Run this command from Workspace root. Missing: ' . $file, 'error');

                return false;
            }
        }

        if (!is_dir($root . '/local')) {
            $io->writeln('Run this command from Workspace root. Missing: local/', 'error');

            return false;
        }

        return true;
    }

    public static function assertInstallRoot(IoInterface $io): bool
    {
        $root = self::root();
        foreach (['compose.yml', 'Makefile', '.env.example', '.workspace.ini.example'] as $file) {
            if (!is_file($root . '/' . $file)) {
                $io->writeln('Run this command from Workspace root. Missing: ' . $file, 'error');

                return false;
            }
        }

        return true;
    }

    public static function failure(): int
    {
        return Response::FAILURE;
    }
}

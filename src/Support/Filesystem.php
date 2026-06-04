<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function basename;
use function copy;
use function dirname;
use function file_exists;
use function is_dir;
use function is_file;
use function mkdir;
use function preg_match;
use function rmdir;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function symlink;
use function unlink;

use const DIRECTORY_SEPARATOR;
final class Filesystem
{
    public function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Cannot create directory: ' . $path);
        }
    }

    public function remove(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            if (!unlink($path)) {
                throw new RuntimeException('Cannot remove file: ' . $path);
            }

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                if (!rmdir($itemPath)) {
                    throw new RuntimeException('Cannot remove directory: ' . $itemPath);
                }
                continue;
            }

            if (!unlink($itemPath)) {
                throw new RuntimeException('Cannot remove file: ' . $itemPath);
            }
        }

        if (!rmdir($path)) {
            throw new RuntimeException('Cannot remove directory: ' . $path);
        }
    }

    /** @param list<string> $exclude */
    public function copyDirectory(string $source, string $target, array $exclude = []): void
    {
        if (!is_dir($source)) {
            throw new RuntimeException('Source directory not found: ' . $source);
        }

        $this->ensureDirectory($target);

        $source = rtrim($source, '/\\');
        $target = rtrim($target, '/\\');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relative   = str_replace('\\', '/', substr($sourcePath, strlen($source) + 1));

            if ($this->isExcluded($relative, $exclude)) {
                continue;
            }

            $targetPath = $target . DIRECTORY_SEPARATOR . $relative;
            if ($item->isDir() && !$item->isLink()) {
                $this->ensureDirectory($targetPath);
                continue;
            }

            $this->ensureDirectory(dirname($targetPath));
            if ($item->isLink()) {
                $linkTarget = (string) $item->getLinkTarget();
                if (!symlink($linkTarget, $targetPath)) {
                    throw new RuntimeException('Cannot copy symlink: ' . $sourcePath);
                }
                continue;
            }

            if (!copy($sourcePath, $targetPath)) {
                throw new RuntimeException('Cannot copy file: ' . $sourcePath);
            }
        }
    }

    public function isLocalDirectory(string $source): bool
    {
        return is_dir($source) || str_starts_with($source, '.') || str_starts_with($source, '/');
    }

    public function assertSafeServiceName(string $service): void
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $service) !== 1) {
            throw new RuntimeException('Invalid service name: ' . $service);
        }
        if ($service === '.' || $service === '..' || basename($service) !== $service) {
            throw new RuntimeException('Invalid service name: ' . $service);
        }
    }

    /** @param list<string> $exclude */
    private function isExcluded(string $relative, array $exclude): bool
    {
        foreach ($exclude as $pattern) {
            if ($relative === $pattern || str_starts_with($relative, rtrim($pattern, '/') . '/')) {
                return true;
            }
        }

        return false;
    }
}

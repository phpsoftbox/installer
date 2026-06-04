<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Support;

use function explode;
use function file;
use function file_put_contents;
use function implode;
use function str_starts_with;

use const FILE_IGNORE_NEW_LINES;

final class EnvFile
{
    public function set(string $file, string $key, string $value): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $lines = [];
        }

        $updated = false;
        foreach ($lines as $index => $line) {
            if (str_starts_with($line, $key . '=')) {
                $lines[$index] = $key . '=' . $value;
                $updated       = true;
                break;
            }
        }

        if (!$updated) {
            $lines[] = $key . '=' . $value;
        }

        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    public function get(string $file, string $key, string $default = ''): string
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return $default;
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, $key . '=')) {
                [, $value] = explode('=', $line, 2);

                return $value;
            }
        }

        return $default;
    }
}

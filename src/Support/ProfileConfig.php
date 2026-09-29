<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Support;

use InvalidArgumentException;

use function array_filter;
use function array_values;
use function file;
use function file_put_contents;
use function implode;
use function in_array;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function trim;

use const FILE_IGNORE_NEW_LINES;
use const PHP_EOL;

final class ProfileConfig
{
    /** @return list<string> */
    public function read(string $file): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!str_starts_with($trimmed, 'default_profiles')) {
                continue;
            }

            $parts = preg_split('/\s*=\s*/', $trimmed, 2);

            return $this->parse($parts[1] ?? '');
        }

        return [];
    }

    /** @param list<string> $profiles */
    public function write(string $file, array $profiles): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false || $lines === []) {
            $lines = ['[profiles]'];
        }

        $value   = 'default_profiles = ' . implode(' ', $profiles);
        $updated = false;
        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'default_profiles')) {
                $lines[$index] = $value;
                $updated       = true;
                break;
            }
        }

        if (!$updated) {
            $lines[] = $value;
        }

        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    /** @param list<string> $values @return list<string> */
    public function normalize(array $values): array
    {
        $profiles = [];
        foreach ($values as $value) {
            foreach ($this->parse($value) as $profile) {
                if (!in_array($profile, $profiles, true)) {
                    $profiles[] = $profile;
                }
            }
        }

        return $profiles;
    }

    /**
     * @return list<string>
     *
     * @throws InvalidArgumentException имя профиля не соответствует формату Docker Compose
     */
    private function parse(string $value): array
    {
        $parts = preg_split('/[\s,]+/', trim($value)) ?: [];

        $profiles = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
        foreach ($profiles as $profile) {
            // Профили уходят в `make PROFILES=...` и `COMPOSE_PROFILES`: `$(shell ...)` make выполнил бы.
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', $profile) !== 1) {
                throw new InvalidArgumentException('Invalid profile name: ' . $profile);
            }
        }

        return $profiles;
    }
}

<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Support;

use InvalidArgumentException;
use PhpSoftBox\Installer\Support\ProfileConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(ProfileConfig::class)]
#[CoversMethod(ProfileConfig::class, 'read')]
#[CoversMethod(ProfileConfig::class, 'write')]
#[CoversMethod(ProfileConfig::class, 'normalize')]
final class ProfileConfigTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'psb-profiles-');
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    /**
     * Проверяем, что профили читаются из default_profiles с разделителями-пробелами и запятыми.
     *
     * @see ProfileConfig::read()
     */
    #[Test]
    public function readsDefaultProfiles(): void
    {
        file_put_contents($this->file, "[profiles]\ndefault_profiles = php-cli, mysql  redis\n");

        $this->assertSame(['php-cli', 'mysql', 'redis'], new ProfileConfig()->read($this->file));
    }

    /**
     * Проверяем, что write заменяет строку default_profiles и сохраняет остальные строки.
     *
     * @see ProfileConfig::write()
     */
    #[Test]
    public function writeReplacesDefaultProfilesLine(): void
    {
        file_put_contents($this->file, "[profiles]\ndefault_profiles = php-cli\nother = 1\n");

        new ProfileConfig()->write($this->file, ['php-cli', 'mongo']);

        $this->assertSame("[profiles]\ndefault_profiles = php-cli mongo\nother = 1\n", file_get_contents($this->file));
    }

    /**
     * Проверяем, что normalize разбирает значения и убирает дубли с сохранением порядка.
     *
     * @see ProfileConfig::normalize()
     */
    #[Test]
    public function normalizeSplitsAndDeduplicates(): void
    {
        $this->assertSame(['a', 'b', 'c'], new ProfileConfig()->normalize(['a,b', 'b c', 'a']));
    }

    /**
     * Проверяем, что имя профиля с make-выражением отклоняется: оно ушло бы в `make PROFILES=...`.
     *
     * @see ProfileConfig::normalize()
     */
    #[Test]
    public function normalizeRejectsUnsafeProfileName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProfileConfig()->normalize(['$(shell touch /tmp/pwn)']);
    }

    /**
     * Проверяем, что недопустимое имя профиля в .workspace.ini тоже отклоняется при чтении.
     *
     * @see ProfileConfig::read()
     */
    #[Test]
    public function readRejectsUnsafeProfileName(): void
    {
        file_put_contents($this->file, "default_profiles = php-cli;id\n");

        $this->expectException(InvalidArgumentException::class);

        new ProfileConfig()->read($this->file);
    }
}

<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Support;

use PhpSoftBox\Installer\Support\EnvFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(EnvFile::class)]
#[CoversMethod(EnvFile::class, 'set')]
#[CoversMethod(EnvFile::class, 'get')]
final class EnvFileTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'psb-env-');
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    /**
     * Проверяем, что set заменяет существующий ключ, дописывает новый и не трогает похожие ключи.
     *
     * @see EnvFile::set()
     * @see EnvFile::get()
     */
    #[Test]
    public function setReplacesExistingAndAppendsNewKey(): void
    {
        file_put_contents($this->file, "BACKEND_PATH=./old\nBACKEND_PATH_EXTRA=keep\n");

        $env = new EnvFile();

        $env->set($this->file, 'BACKEND_PATH', './local/api');
        $env->set($this->file, 'PHP_IDE_CONFIG', 'serverName=api');

        $this->assertSame(
            "BACKEND_PATH=./local/api\nBACKEND_PATH_EXTRA=keep\nPHP_IDE_CONFIG=serverName=api\n",
            file_get_contents($this->file),
        );
        // Значение со знаком "=" возвращается целиком.
        $this->assertSame('serverName=api', $env->get($this->file, 'PHP_IDE_CONFIG'));
    }
}

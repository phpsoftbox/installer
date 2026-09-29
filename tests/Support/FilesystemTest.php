<?php

declare(strict_types=1);

namespace PhpSoftBox\Installer\Tests\Support;

use PhpSoftBox\Installer\Support\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_exists;
use function file_put_contents;
use function is_link;
use function mkdir;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(Filesystem::class)]
#[CoversMethod(Filesystem::class, 'copyDirectory')]
#[CoversMethod(Filesystem::class, 'remove')]
#[CoversMethod(Filesystem::class, 'assertSafeServiceName')]
final class FilesystemTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/psb-fs-' . uniqid('', true);
        mkdir($this->root, 0775, true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->root);
    }

    /**
     * Проверяем, что copyDirectory копирует файлы и пропускает исключённые пути с их содержимым.
     *
     * @see Filesystem::copyDirectory()
     */
    #[Test]
    public function copyDirectorySkipsExcludedPaths(): void
    {
        mkdir($this->root . '/src/.git', 0775, true);
        mkdir($this->root . '/src/app', 0775, true);
        file_put_contents($this->root . '/src/.git/HEAD', 'ref');
        file_put_contents($this->root . '/src/.env', 'SECRET=1');
        file_put_contents($this->root . '/src/app/index.php', '<?php');

        new Filesystem()->copyDirectory($this->root . '/src', $this->root . '/dst', ['.git', '.env']);

        $this->assertFileExists($this->root . '/dst/app/index.php');
        $this->assertFileDoesNotExist($this->root . '/dst/.env');
        $this->assertDirectoryDoesNotExist($this->root . '/dst/.git');
    }

    /**
     * Проверяем, что remove удаляет симлинк на каталог, не заходя в целевой каталог.
     *
     * @see Filesystem::remove()
     */
    #[Test]
    public function removeDoesNotFollowDirectorySymlinks(): void
    {
        mkdir($this->root . '/outside', 0775, true);
        file_put_contents($this->root . '/outside/keep.txt', 'keep');
        mkdir($this->root . '/target', 0775, true);
        symlink($this->root . '/outside', $this->root . '/target/link');

        new Filesystem()->remove($this->root . '/target');

        $this->assertFalse(file_exists($this->root . '/target') || is_link($this->root . '/target/link'));
        $this->assertFileExists($this->root . '/outside/keep.txt');
    }

    /**
     * Проверяем, что имена сервисов с путями и спецсимволами отклоняются.
     *
     * @see Filesystem::assertSafeServiceName()
     */
    #[Test]
    #[DataProvider('unsafeServiceNames')]
    public function assertSafeServiceNameRejectsUnsafeNames(string $service): void
    {
        $this->expectException(RuntimeException::class);

        new Filesystem()->assertSafeServiceName($service);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeServiceNames(): iterable
    {
        yield 'empty' => [''];
        yield 'parent' => ['..'];
        yield 'path' => ['../etc'];
        yield 'leading dash' => ['-rf'];
        yield 'space' => ['my app'];
    }
}

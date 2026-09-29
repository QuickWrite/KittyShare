<?php

use KittyShare\Filesystem\DirectoryBrowser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DirectoryBrowserTest extends TestCase
{
    private string $base = '';
    private string $root = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->base = sys_get_temp_dir() . '/kittyshare-dir-' . bin2hex(random_bytes(4));

        mkdir($this->base . '/root/sub', 0777, true);
        file_put_contents($this->base . '/root/top.txt', 'top');
        file_put_contents($this->base . '/root/sub/inner.txt', 'inner');
        file_put_contents($this->base . '/root/.hidden', 'h');

        $this->root = (string) realpath($this->base . '/root');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);

        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        if ($dir === '' || !str_starts_with($dir, $tmp . DIRECTORY_SEPARATOR . 'kittyshare-')) {
            return;
        }

        if (is_link($dir)) {
            unlink($dir);

            return;
        }

        if (!is_dir($dir)) {
            if (is_file($dir)) {
                unlink($dir);
            }

            return;
        }

        foreach (scandir($dir) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }

            $this->removeDir($dir . DIRECTORY_SEPARATOR . $e);
        }

        rmdir($dir);
    }

    #[Test]
    public function resolveValidPath(): void
    {
        $this->assertSame($this->root . DIRECTORY_SEPARATOR . 'top.txt', DirectoryBrowser::resolvePath($this->root, 'top.txt'));
        $this->assertSame($this->root, DirectoryBrowser::resolvePath($this->root, 'sub/..'));
    }

    #[Test]
    public function resolveTraversalReturnsNull(): void
    {
        $this->assertNull(DirectoryBrowser::resolvePath($this->root, '../top.txt'));
        $this->assertNull(DirectoryBrowser::resolvePath($this->root, 'missing.txt'));
    }

    #[Test]
    public function isWithinRootChecksPrefix(): void
    {
        $this->assertTrue(DirectoryBrowser::isWithinRoot($this->root, $this->root));
        $this->assertTrue(DirectoryBrowser::isWithinRoot($this->root, $this->root . '/sub'));
        $this->assertFalse(DirectoryBrowser::isWithinRoot($this->root, $this->root . '-other'));
    }

    #[Test]
    public function relativePathStripsRoot(): void
    {
        $this->assertSame('', DirectoryBrowser::relativePath($this->root, $this->root));
        $this->assertSame('sub', DirectoryBrowser::relativePath($this->root, $this->root . '/sub'));
    }

    #[Test]
    public function listDirectoryHidesDotfilesByDefault(): void
    {
        $names = array_column(DirectoryBrowser::listDirectory($this->root, '', false), 'name');

        $this->assertContains('top.txt', $names);
        $this->assertNotContains('.hidden', $names);
        $this->assertContains('.hidden', array_column(DirectoryBrowser::listDirectory($this->root, '', true), 'name'));
    }

    #[Test]
    public function listingFilesystemRootIsNotEmpty(): void
    {
        if (scandir('/') === false) {
            $this->markTestSkipped('filesystem root is not readable');
        }

        $entries = DirectoryBrowser::listDirectory('/', '', true);

        $this->assertNotEmpty($entries, 'listing / must not filter out every entry');
    }

    #[Test]
    public function symlinkedDirOutsideIsNotListedAsDir(): void
    {
        $outside = sys_get_temp_dir() . '/kittyshare-dir-outside-' . bin2hex(random_bytes(4));
        mkdir($outside, 0777, true);
        $link = $this->root . '/evil-dir';

        if (!symlink($outside, $link)) {

            $this->markTestSkipped('symlink not permitted');
        }

        try {
            $byName = [];
            foreach (DirectoryBrowser::listDirectory($this->root, '', false) as $entry) {
                $byName[$entry['name']] = $entry['isDir'];
            }

            $this->assertArrayNotHasKey('evil-dir', $byName, 'symlink escaping the root must not be listed');
        } finally {
            unlink($link);
            rmdir($outside);
        }
    }
}

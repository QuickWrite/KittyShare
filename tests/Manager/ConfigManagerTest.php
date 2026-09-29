<?php

use KittyShare\Http\FileServerType;
use KittyShare\Manager\ConfigManager;
use KittyShare\Manager\DependencyManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigManagerTest extends TestCase
{
    private const ENV_VARS = [
        'KITTYSHARE_DATABASE_PATH',
        'KITTYSHARE_ROOT',
        'KITTYSHARE_SESSION_LIFETIME',
        'KITTYSHARE_COOKIE_SAMESITE',
        'KITTYSHARE_COOKIE_SECURE',
        'KITTYSHARE_SHOW_DOTFILES',
        'KITTYSHARE_BASE_URL',
        'KITTYSHARE_DOWNLOAD_CHUNK_SIZE',
        'KITTYSHARE_META_DESCRIPTION',
        'KITTYSHARE_META_OG_MODE',
        'KITTYSHARE_FILE_SERVER',
    ];

    /** @var array<string, string|false> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENV_VARS as $name) {
            $this->originalEnv[$name] = getenv($name);
            putenv($name);
        }

        $this->resetConfig();
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_VARS as $name) {
            $original = $this->originalEnv[$name] ?? false;

            if ($original === false) {
                putenv($name);
            } else {
                putenv($name . '=' . $original);
            }
        }

        $this->resetConfig();

        parent::tearDown();
    }

    private function resetConfig(): void
    {
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        (new ReflectionProperty(DependencyManager::class, 'dependencies'))->setValue(null, null);
    }

    private function configure(array $env): void
    {
        foreach ($env as $name => $value) {
            putenv($name . '=' . $value);
        }

        $this->resetConfig();
    }

    #[Test]
    public function returnsSameInstanceWhileCached(): void
    {
        $this->assertSame(ConfigManager::get(), ConfigManager::get());
    }

    #[Test]
    public function rereadsEnvAfterReset(): void
    {
        $this->configure(['KITTYSHARE_SESSION_LIFETIME' => '100']);

        $this->assertSame(100, ConfigManager::get()->sessionLifetime);

        $this->configure(['KITTYSHARE_SESSION_LIFETIME' => '200']);

        $this->assertSame(200, ConfigManager::get()->sessionLifetime);
    }

    #[Test]
    public function defaultsWhenEnvUnset(): void
    {
        $config = ConfigManager::get();

        $managerDir = dirname((new ReflectionClass(ConfigManager::class))->getFileName());

        $this->assertSame($managerDir . '/../../database.sqlite', $config->databasePath);
        $this->assertSame(realpath('/') ?: '/', $config->browseRoot);
        $this->assertSame(60 * 60 * 24 * 30, $config->sessionLifetime);
        $this->assertSame('Lax', $config->cookieSameSite);
        $this->assertNull($config->cookieSecure);
        $this->assertFalse($config->showDotfiles);
        $this->assertNull($config->baseUrl);
        $this->assertSame(8192, $config->downloadChunkSize);
        $this->assertNull($config->metaDescription);
        $this->assertSame('none', $config->metaOgMode);
        $this->assertSame(FileServerType::Php, $config->fileServer);
    }

    #[Test]
    public function readsDatabasePathFromEnv(): void
    {
        $this->configure(['KITTYSHARE_DATABASE_PATH' => '/tmp/custom.sqlite']);

        $this->assertSame('/tmp/custom.sqlite', ConfigManager::get()->databasePath);
    }

    #[Test]
    public function emptyDatabasePathFallsBackToDefault(): void
    {
        putenv('KITTYSHARE_DATABASE_PATH=');
        $this->resetConfig();

        $this->assertStringEndsWith('database.sqlite', ConfigManager::get()->databasePath);
    }

    #[Test]
    public function resolvesBrowseRootToRealpath(): void
    {
        $dir = sys_get_temp_dir();
        $this->configure(['KITTYSHARE_ROOT' => $dir]);

        $this->assertSame(realpath($dir), ConfigManager::get()->browseRoot);
    }

    #[Test]
    public function invalidBrowseRootThrows(): void
    {
        $this->configure(['KITTYSHARE_ROOT' => '/definitely-not-a-kittyshare-dir-12345']);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function fileBrowseRootThrows(): void
    {
        $this->configure(['KITTYSHARE_ROOT' => __FILE__]);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function readsSessionLifetimeFromEnv(): void
    {
        $this->configure(['KITTYSHARE_SESSION_LIFETIME' => '3600']);

        $this->assertSame(3600, ConfigManager::get()->sessionLifetime);
    }

    #[Test]
    public function invalidSessionLifetimeThrows(): void
    {
        foreach (['0', '-5', 'abc', '3.5', ' 10', '10 '] as $value) {
            $this->configure(['KITTYSHARE_SESSION_LIFETIME' => $value]);

            try {
                ConfigManager::get();
                $thrown = false;
            } catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "session lifetime '$value' must throw");
        }
    }

    #[Test]
    public function readsDownloadChunkSizeFromEnv(): void
    {
        $this->configure(['KITTYSHARE_DOWNLOAD_CHUNK_SIZE' => '4096']);

        $this->assertSame(4096, ConfigManager::get()->downloadChunkSize);
    }

    #[Test]
    public function invalidDownloadChunkSizeThrows(): void
    {
        foreach (['0', '-1', 'huge', '8.5'] as $value) {
            $this->configure(['KITTYSHARE_DOWNLOAD_CHUNK_SIZE' => $value]);

            try {
                ConfigManager::get();
                $thrown = false;
            } catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "chunk size '$value' must throw");
        }
    }

    #[Test]
    public function normalizesCookieSameSite(): void
    {
        foreach (['lax' => 'Lax', 'LAX' => 'Lax', 'strict' => 'Strict', 'STRICT' => 'Strict'] as $raw => $expected) {
            $this->configure(['KITTYSHARE_COOKIE_SAMESITE' => $raw]);

            $this->assertSame($expected, ConfigManager::get()->cookieSameSite, "SameSite '$raw'");
        }

        foreach (['none' => 'None', 'NONE' => 'None'] as $raw => $expected) {
            $this->configure(['KITTYSHARE_COOKIE_SAMESITE' => $raw, 'KITTYSHARE_COOKIE_SECURE' => 'true']);

            $this->assertSame($expected, ConfigManager::get()->cookieSameSite, "SameSite '$raw'");
        }
    }

    #[Test]
    public function sameSiteNoneWithExplicitInsecureThrows(): void
    {
        foreach (['false', '0', 'no', 'off'] as $secure) {
            $this->configure(['KITTYSHARE_COOKIE_SAMESITE' => 'none', 'KITTYSHARE_COOKIE_SECURE' => $secure]);

            try {
                ConfigManager::get();
                $thrown = false;
            } catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "SameSite=None with Secure=$secure must throw");
        }
    }

    #[Test]
    public function sameSiteNoneWithAutoSecureIsAllowed(): void
    {
        $this->configure(['KITTYSHARE_COOKIE_SAMESITE' => 'none']);

        $this->assertSame('None', ConfigManager::get()->cookieSameSite);
    }

    #[Test]
    public function invalidCookieSameSiteThrows(): void
    {
        foreach (['sometimes', 'null'] as $value) {
            $this->configure(['KITTYSHARE_COOKIE_SAMESITE' => $value]);

            try {
                ConfigManager::get();
                $thrown = false;
            } catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "SameSite '$value' must throw");
        }
    }

    #[Test]
    public function parsesCookieSecureTruthyValues(): void
    {
        foreach (['1', 'true', 'TRUE', 'yes', 'Yes', 'on', 'ON'] as $value) {
            $this->configure(['KITTYSHARE_COOKIE_SECURE' => $value]);

            $this->assertTrue(ConfigManager::get()->cookieSecure, "cookie secure '$value' must be true");
        }
    }

    #[Test]
    public function parsesCookieSecureFalsyValues(): void
    {
        foreach (['0', 'false', 'FALSE', 'no', 'No', 'off', 'OFF'] as $value) {
            $this->configure(['KITTYSHARE_COOKIE_SECURE' => $value]);

            $this->assertFalse(ConfigManager::get()->cookieSecure, "cookie secure '$value' must be false");
        }
    }

    #[Test]
    public function invalidCookieSecureThrows(): void
    {
        foreach (['2', 'maybe'] as $value) {
            $this->configure(['KITTYSHARE_COOKIE_SECURE' => $value]);

            try {
                ConfigManager::get();
                $thrown = false;
            } catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "cookie secure '$value' must throw");
        }
    }

    #[Test]
    public function parsesShowDotfiles(): void
    {
        $this->configure(['KITTYSHARE_SHOW_DOTFILES' => 'true']);

        $this->assertTrue(ConfigManager::get()->showDotfiles);

        $this->configure(['KITTYSHARE_SHOW_DOTFILES' => '1']);

        $this->assertTrue(ConfigManager::get()->showDotfiles);

        $this->configure(['KITTYSHARE_SHOW_DOTFILES' => '0']);

        $this->assertFalse(ConfigManager::get()->showDotfiles);
    }

    #[Test]
    public function invalidShowDotfilesThrows(): void
    {
        $this->configure(['KITTYSHARE_SHOW_DOTFILES' => 'maybe']);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function baseUrlDefaultsToNull(): void
    {
        $this->assertNull(ConfigManager::get()->baseUrl);
        $this->assertSame('', ConfigManager::basePath());
    }

    #[Test]
    public function baseUrlKeepsFullUrlsAndStripsTrailingSlash(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => 'https://files.example.com/test/']);

        $this->assertSame('https://files.example.com/test', ConfigManager::get()->baseUrl);
    }

    #[Test]
    public function baseUrlKeepsBarePaths(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => '/public/']);

        $this->assertSame('/public', ConfigManager::get()->baseUrl);
    }

    #[Test]
    public function baseUrlPrefixesHostWithPath(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => '127.0.0.1:3000/public']);

        $this->assertSame('http://127.0.0.1:3000/public', ConfigManager::get()->baseUrl);
    }

    #[Test]
    public function baseUrlRejectsBareHostWithoutPath(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => 'example.com']);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function baseUrlRejectsEmptyValues(): void
    {
        foreach (['', '   ', '/'] as $value) {
            $this->configure(['KITTYSHARE_BASE_URL' => $value]);

            $this->assertNull(ConfigManager::get()->baseUrl, "base URL '$value' must be null");
        }
    }

    #[Test]
    public function baseUrlTrimsSurroundingWhitespace(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => '  /public/  ']);

        $this->assertSame('/public', ConfigManager::get()->baseUrl);
    }

    #[Test]
    public function basePathExtractsPathFromFullUrl(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => 'https://files.example.com/test/abc']);

        $this->assertSame('/test/abc', ConfigManager::basePath());
    }

    #[Test]
    public function basePathIsEmptyWithoutPath(): void
    {
        foreach (['https://files.example.com', 'https://files.example.com/'] as $value) {
            $this->configure(['KITTYSHARE_BASE_URL' => $value]);

            $this->assertSame('', ConfigManager::basePath(), "base path of '$value' must be empty");
        }
    }

    #[Test]
    public function basePathReturnsBarePath(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => '/public']);

        $this->assertSame('/public', ConfigManager::basePath());
    }

    #[Test]
    public function metaDescriptionDefaultsToNull(): void
    {
        $this->assertNull(ConfigManager::get()->metaDescription);
    }

    #[Test]
    public function metaDescriptionKeepsConfiguredText(): void
    {
        $this->configure(['KITTYSHARE_META_DESCRIPTION' => 'Share files simply']);

        $this->assertSame('Share files simply', ConfigManager::get()->metaDescription);
    }

    #[Test]
    public function blankMetaDescriptionMeansOmitTag(): void
    {
        foreach (['', '   '] as $value) {
            $this->configure(['KITTYSHARE_META_DESCRIPTION' => $value]);

            $this->assertNull(ConfigManager::get()->metaDescription, 'blank description must be null');
        }
    }

    #[Test]
    public function parsesMetaOgModeWithAliases(): void
    {
        foreach (['minimal' => 'minimal', 'MINIMAL' => 'minimal', 'per-share' => 'per-share', 'per_share' => 'per-share', 'full' => 'per-share', 'per-share-full' => 'per-share', ' none ' => 'none'] as $raw => $expected) {
            $this->configure(['KITTYSHARE_META_OG_MODE' => (string) $raw]);

            $this->assertSame($expected, ConfigManager::get()->metaOgMode, "og mode '$raw'");
        }
    }

    #[Test]
    public function invalidMetaOgModeThrows(): void
    {
        $this->configure(['KITTYSHARE_META_OG_MODE' => 'everything']);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function parsesFileServerBackends(): void
    {
        foreach (['php' => FileServerType::Php, 'php-stream' => FileServerType::Php, 'x-sendfile' => FileServerType::XSendfile, 'apache' => FileServerType::XSendfile, ' X-SENDFILE ' => FileServerType::XSendfile] as $raw => $expected) {
            $this->configure(['KITTYSHARE_FILE_SERVER' => $raw]);

            $this->assertSame($expected, ConfigManager::get()->fileServer, "file server '$raw'");
        }
    }

    #[Test]
    public function unknownFileServerThrows(): void
    {
        $this->configure(['KITTYSHARE_FILE_SERVER' => 'nginx']);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function protocolRelativeBaseUrlThrows(): void
    {
        $this->configure(['KITTYSHARE_BASE_URL' => '//evil.com/foo']);

        $this->expectException(RuntimeException::class);
        ConfigManager::get();
    }

    #[Test]
    public function baseUrlWithQuotesThrows(): void
    {
        foreach (['/app"><script', '/app\'onload=x', '/a"b'] as $value) {
            $this->configure(['KITTYSHARE_BASE_URL' => $value]);

            try {
                ConfigManager::get();
                $thrown = false;
            } catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "base URL '$value' must throw");
        }
    }
}

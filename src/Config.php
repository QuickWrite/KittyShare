<?php

namespace KittyShare;

/**
 * Centralized, typesafe application configuration.
 */
final readonly class Config
{
    public function __construct(
        /** Path to the SQLite database file. */
        public string $databasePath,

        /** Root directory the admin file picker may browse and share from. */
        public string $browseRoot,

        /** Session lifetime in seconds. */
        public int $sessionLifetime,

        /** Session cookie SameSite policy: Lax, Strict or None. */
        public string $cookieSameSite,

        /**
         * Session cookie Secure flag:
         * - true sends it over HTTPS only,
         * - false also over HTTP,
         * - null follows whether the request uses HTTPS.
         */
        public ?bool $cookieSecure,

        /** Whether directory listings include entries starting with a dot. */
        public bool $showDotfiles,

        /** Canonical base URL for absolute share links, or null for relative links. */
        public ?string $baseUrl,

        /** Number of bytes sent per chunk when downloading files. */
        public int $downloadChunkSize,
    ) {
    }
}

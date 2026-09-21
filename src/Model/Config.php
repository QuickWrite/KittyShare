<?php

namespace KittyShare\Model;


final readonly class Config
{
    /**
     * Centralized, typesafe application configuration.
     * 
     * @param string $databasePath
     * @param string $browseRoot
     * @param positive-int $sessionLifetime
     * @param 'Lax'|'Strict'|'None' $cookieSameSite
     * @param ?bool $cookieSecure
     * @param bool $showDotfiles
     * @param ?string $baseUrl
     * @param positive-int $downloadChunkSize
     * @param ?string $metaDescription
     * @param 'none'|'minimal'|'per-share' $metaOgMode
     */
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

        /**
         * Generic meta description text, or null to omit the tag.
         */
        public ?string $metaDescription,

        /**
         * Open Graph extensiveness:
         * - 'none' omits all og:* tags,
         * - 'minimal' emits only generic site tags,
         * - 'per-share' additionally allows per-share og:title.
         */
        public string $metaOgMode,
    ) {
    }
}

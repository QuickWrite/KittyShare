<?php

namespace KittyShare\Manager;

use KittyShare\Model\Config;

final class ConfigManager {
    private static ?Config $config = null;

    public static function get(): Config
    {
        if (self::$config == null) {
            self::$config = self::constructConfig();
        }

        return self::$config;
    }

    private static function constructConfig(): Config
    {
        return new Config(
            databasePath: self::env('KITTYSHARE_DATABASE_PATH')
                ?? __DIR__ . '/../database.sqlite',
            browseRoot: self::resolveBrowseRoot(
                self::env('KITTYSHARE_ROOT')
            ),
            sessionLifetime: self::envPosInt(
                'KITTYSHARE_SESSION_LIFETIME',
                60 * 60 * 24 * 30,
            ),
            cookieSameSite: self::cookieSameSite(),
            cookieSecure: self::envBoolOrNull('KITTYSHARE_COOKIE_SECURE'),
            showDotfiles: self::envBool('KITTYSHARE_SHOW_DOTFILES', false),
            baseUrl: self::baseUrl(),
            downloadChunkSize: self::envPosInt(
                'KITTYSHARE_DOWNLOAD_CHUNK_SIZE',
                8192,
            ),
            metaDescription: self::metaDescription(),
            metaOgMode: self::metaOgMode(),
        );
    }

    private static function resolveBrowseRoot(?string $configured): string
    {
        if ($configured !== null && $configured !== '') {
            $root = realpath($configured);

            if ($root !== false && is_dir($root)) {
                return $root;
            }
        }

        $root = realpath('/');

        return $root !== false ? $root : '/';
    }

    /**
     * @return string|null The value, or null when not set.
     */
    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Reads a positive integer setting in seconds/bytes.
     *
     * @param string        $name    The name of the value
     * @param positive-int  $default The default value
     * @return positive-int The configured value, or $default when unset or invalid.
     */
    private static function envPosInt(string $name, int $default): int
    {
        $value = self::env($name);

        if ($value === null || !ctype_digit($value)) {
            return $default;
        }

        $parsed = (int) $value;

        return $parsed > 0 ? $parsed : $default;
    }

    /**
     * Reads a boolean setting.
     *
     * Accepts 1/true/yes/on and 0/false/no/off (case-insensitive).
     *
     * @return bool The configured value, or $default when unset or invalid.
     */
    private static function envBool(string $name, bool $default): bool
    {
        $parsed = self::envBoolOrNull($name);

        return $parsed ?? $default;
    }

    /**
     * Reads a boolean setting where null means "not configured".
     *
     * @return bool|null The configured value, or null when unset or invalid.
     */
    private static function envBoolOrNull(string $name): ?bool
    {
        $value = self::env($name);

        if ($value === null) {
            return null;
        }

        return match (strtolower($value)) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    /**
     * @return 'Lax'|'Strict'|'None' One of Lax, Strict or None; Lax when unset or invalid.
     */
    private static function cookieSameSite(): string
    {
        $value = self::env('KITTYSHARE_COOKIE_SAMESITE');

        if ($value === null) {
            return 'Lax';
        }

        return match (strtolower($value)) {
            'lax' => 'Lax',
            'strict' => 'Strict',
            'none' => 'None',
            default => 'Lax',
        };
    }

    /**
     * Generates the base url for the given environment variable.
     *
     * Accepts:
     * - Full URLs: "https://files.example.com/test"
     * - Host+path without protocol: "127.0.0.1:3000/public"
     * - Bare paths: "/public"
     *
     * @return string|null The base URL without trailing slash, or null when
     *                     unset or invalid.
     */
    private static function baseUrl(): ?string
    {
        $value = self::env('KITTYSHARE_BASE_URL');

        if ($value === null) {
            return null;
        }

        $value = rtrim(trim($value), '/');

        if ($value === '') {
            return null;
        }

        if (str_starts_with(strtolower($value), 'http://') || str_starts_with(strtolower($value), 'https://')) {
            return $value;
        }

        if ($value[0] === '/') {
            return $value;
        }

        if (str_contains($value, '/')) {
            return 'http://' . $value;
        }

        return null;
    }

    /**
     * Reads the generic meta description.
     *
     * Empty/unset means "omit the tag" (default).
     *
     * @return string|null The configured text, or null when unset.
     */
    private static function metaDescription(): ?string
    {
        $value = self::env('KITTYSHARE_META_DESCRIPTION');

        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * Reads the Open Graph extensiveness mode.
     *
     * @return 'none'|'minimal'|'per-share' The configured mode; 'none' when unset or invalid.
     */
    private static function metaOgMode(): string
    {
        $value = self::env('KITTYSHARE_META_OG_MODE');

        if ($value === null) {
            return 'none';
        }

        return match (strtolower(trim($value))) {
            'none' => 'none',
            'minimal' => 'minimal',
            'per-share', 'per_share', 'full', 'per-share-full' => 'per-share',
            default => 'none',
        };
    }

    /**
     * Returns the path portion of the base URL for use as an internal route
     * prefix.
     *
     * For example, "https://files.example.com/test/abc" returns "/test/abc".
     *
     * Returns "" when no base URL is configured or when the path is just "/".
     *
     * @return string The base path
     */
    public static function basePath(): string
    {
        $base = self::get()->baseUrl;

        if ($base === null) {
            return '';
        }

        $path = parse_url($base, PHP_URL_PATH);

        if (!is_string($path) || $path === '' || $path === '/') {
            return '';
        }

        return $path;
    }
}

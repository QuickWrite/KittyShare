<?php

namespace KittyShare\Http;

use KittyShare\ConfigManager;
use RuntimeException;

/**
 * Wrapper for everything PHP session related.
 *
 * The session is started lazily: the first get/set/pull/remove/regenerate
 * call applies the cookie configuration once and starts the session once.
 * Code paths that never touch the session (e.g. public share downloads)
 * therefore never create one. All session calls must happen before any
 * output is sent.
 */
final class Session
{
    private static bool $started = false;

    /**
     * Reads a value from the session.
     *
     * @return mixed The stored value, or $default when the key is missing.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Stores a value in the session.
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();

        $_SESSION[$key] = $value;
    }

    /**
     * Removes one or more keys from the session.
     */
    public static function remove(string ...$keys): void
    {
        self::ensureStarted();

        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Reads a value and removes it in one call (e.g. flash messages).
     *
     * @return mixed The stored value, or $default when the key is missing.
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();

        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    /**
     * Regenerates the PHP session ID and deletes the old session data.
     */
    public static function regenerate(): void
    {
        self::ensureStarted();

        if (headers_sent()) {
            throw new RuntimeException('Session must be used before any output is sent.');
        }

        session_regenerate_id(true);
    }

    private static function ensureStarted(): void
    {
        if (self::$started) {
            return;
        }

        if (headers_sent()) {
            throw new RuntimeException('Session must be used before any output is sent.');
        }

        self::configure();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        self::$started = true;
    }

    private static function configure(): void
    {
        $config = ConfigManager::get();

        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        $cookieSecure = $config->cookieSecure ?? $isHttps;

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $cookieSecure,
            'httponly' => true,
            'samesite' => $config->cookieSameSite,
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', $config->cookieSameSite);
        ini_set('session.cookie_secure', $cookieSecure ? '1' : '0');
    }
}

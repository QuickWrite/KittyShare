<?php

namespace KittyShare\Filesystem;

use function is_dir;
use function ltrim;
use function realpath;
use function scandir;
use function str_starts_with;
use function substr;
use function strlen;

/**
 * Read-only filesystem browser.
 *
 * Centralizes directory listing and path resolution so that the public
 * share view and the admin file picker share the same logic.
 */
final class DirectoryBrowser
{
    /**
     * Returns the filesystem root the admin file picker may browse.
     */
    public static function browseRoot(): string
    {
        // TODO: make the browse root server-configured via environment variables (e.g. KITTYSHARE_ROOT).
        $root = realpath('/');

        return $root !== false ? $root : '/';
    }

    /**
     * Resolves a user-provided path within a root directory.
     *
     * @param string $root The canonical absolute path to the root.
     * @param string $path The user-provided path relative to the root.
     *
     * @return string|null The canonical absolute path when valid; null otherwise.
     */
    public static function resolvePath(
        string $root,
        string $path,
    ): ?string {
        $resolvedPath = realpath(
            $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\')
        );

        if ($resolvedPath === false) {
            return null;
        }

        if (!self::isWithinRoot($root, $resolvedPath)) {
            return null;
        }

        return $resolvedPath;
    }

    /**
     * Checks that a canonical path is the root itself or contained in it.
     *
     * @param string $root         The canonical absolute root path.
     * @param string $resolvedPath The canonical absolute path to check.
     */
    public static function isWithinRoot(
        string $root,
        string $resolvedPath,
    ): bool {
        if ($resolvedPath === $root) {
            return true;
        }

        // The filesystem root contains every absolute path. Without this
        // special case the prefix check below would compare against "//".
        if ($root === DIRECTORY_SEPARATOR) {
            return str_starts_with($resolvedPath, DIRECTORY_SEPARATOR);
        }

        // Allow a path whose next character after the root is a directory
        // separator. The separator check prevents "/shares/foo-bar" from
        // matching "/shares/foo".
        return str_starts_with(
            $resolvedPath,
            $root . DIRECTORY_SEPARATOR,
        );
    }

    /**
     * Returns the path relative to the root.
     *
     * Both paths must be canonical absolute paths.
     *
     * @param string $root         The canonical absolute root.
     * @param string $resolvedPath The canonical absolute path within the root.
     *
     * @return string The path relative to the root.
     */
    public static function relativePath(
        string $root,
        string $resolvedPath,
    ): string {
        if ($resolvedPath === $root) {
            return '';
        }

        if ($root === DIRECTORY_SEPARATOR) {
            return ltrim($resolvedPath, DIRECTORY_SEPARATOR);
        }

        return ltrim(
            substr($resolvedPath, strlen($root)),
            DIRECTORY_SEPARATOR,
        );
    }

    /**
     * Lists the immediate contents of a directory.
     *
     * The returned paths are relative URL paths within the root and are
     * not filesystem paths.
     *
     * @param string $directoryPath The canonical absolute directory path.
     * @param string $relativePath  The directory path relative to the root.
     *
     * @return list<array{
     *     name: string,
     *     isDir: bool,
     *     relativePath: string
     * }>
     */
    public static function listDirectory(
        string $directoryPath,
        string $relativePath,
    ): array {
        $entries = scandir($directoryPath);

        if ($entries === false) {
            return [];
        }

        $result = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $directoryPath . DIRECTORY_SEPARATOR . $entry;
            $entryRelativePath = $relativePath === ''
                ? $entry
                : $relativePath . '/' . $entry;

            $result[] = [
                'name' => $entry,
                'isDir' => is_dir($entryPath),
                'relativePath' => $entryRelativePath,
            ];
        }

        return $result;
    }
}

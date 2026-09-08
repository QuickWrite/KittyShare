<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response, TemplateResponse, FileResponse};
use KittyShare\Model\Share;
use Override;
use function is_dir;
use function realpath;
use function scandir;
use function basename;
use function is_file;
use function mime_content_type;
use function str_starts_with;

final class ShareController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        $id = $request->urlParam('id');
        $path = $request->urlParam('path');

        $share = $this->dependencies->shareRepository->find($id);

        if ($share === null || !$share->isActive()) {
            return $this->notFound();
        }

        // Resolve the share root once. From this point on, only use the
        // canonical path so that symlinks and relative path components
        // cannot bypass the security checks.
        $shareRoot = realpath($share->filepath);

        if ($shareRoot === false) {
            return $this->notFound();
        }

        if (is_file($shareRoot)) {
            return $this->handleFileRoot($share, $shareRoot, $path);
        }

        if (!is_dir($shareRoot)) {
            return $this->notFound();
        }

        if ($path === null || $path === '') {
            return $this->renderDirectory(
                share: $share,
                directoryPath: $shareRoot,
                relativePath: '',
            );
        }

        $resolvedPath = $this->resolveSharePath($shareRoot, $path);

        if ($resolvedPath === null) {
            return $this->notFound();
        }

        if (is_file($resolvedPath)) {
            return $this->serveFile($resolvedPath);
        }

        if (!is_dir($resolvedPath)) {
            return $this->notFound();
        }

        // The path has been canonicalized and verified to be inside the
        // share. Generate the relative path from the canonical filesystem
        // path rather than passing the original user input to the template.
        $relativePath = $this->relativePath($shareRoot, $resolvedPath);

        return $this->renderDirectory(
            share: $share,
            directoryPath: $resolvedPath,
            relativePath: $relativePath,
        );
    }

    /**
     * Handles a share whose root is a single file.
     *
     * The file is exposed at /share/{id}/{filename}, while /share/{id}
     * renders a directory-like listing containing the single file.
     *
     * @param Share       $share     The share being accessed.
     * @param string      $shareRoot The canonical absolute path to the file.
     * @param string|null $path      The optional path requested by the user.
     *
     * @return Response The file response or directory listing.
     */
    private function handleFileRoot(
        Share $share,
        string $shareRoot,
        ?string $path,
    ): Response {
        $basename = basename($shareRoot);

        if ($path === null || $path === '') {
            return new TemplateResponse(
                'share/directory',
                [
                    'base' => $basename,
                    'share' => $share,
                    'relativePath' => '',
                    'entries' => [
                        [
                            'name' => $basename,
                            'isDir' => false,
                            'relativePath' => $basename,
                        ],
                    ],
                ]
            );
        }

        // A file-root share only has one valid path: its filename.
        if ($path !== $basename) {
            return $this->notFound();
        }

        return $this->serveFile($shareRoot);
    }

    /**
     * Resolves a user-provided path within a share.
     *
     * The requested path is resolved using {@see realpath()}, which
     * canonicalizes relative path components and symbolic links. The
     * resulting path is returned only when it exists and is contained
     * within the canonical share root.
     *
     * This prevents path traversal and symbolic-link escapes outside
     * the share.
     *
     * @param string $shareRoot The canonical absolute path to the share root.
     * @param string $path      The user-provided path relative to the share root.
     *
     * @return string|null The canonical absolute path when valid; null otherwise.
     */
    private function resolveSharePath(
        string $shareRoot,
        string $path,
    ): ?string {
        $resolvedPath = realpath(
            $shareRoot . DIRECTORY_SEPARATOR . ltrim($path, '/\\')
        );

        if ($resolvedPath === false) {
            return null;
        }

        // Allow the share root itself, or a path whose next character
        // after the root is a directory separator. The separator check
        // prevents "/shares/foo-bar" from matching "/shares/foo".
        if (
            $resolvedPath !== $shareRoot
            && !str_starts_with(
                $resolvedPath,
                $shareRoot . DIRECTORY_SEPARATOR,
            )
        ) {
            return null;
        }

        return $resolvedPath;
    }

    /**
     * Returns the path relative to the share root.
     *
     * Both paths must be canonical absolute paths.
     *
     * @param string $shareRoot    The canonical absolute share root.
     * @param string $resolvedPath The canonical absolute path within the share.
     *
     * @return string The path relative to the share root.
     */
    private function relativePath(
        string $shareRoot,
        string $resolvedPath,
    ): string {
        if ($resolvedPath === $shareRoot) {
            return '';
        }

        return ltrim(
            substr($resolvedPath, strlen($shareRoot)),
            DIRECTORY_SEPARATOR,
        );
    }

    /**
     * Renders a directory listing.
     *
     * @param Share  $share          The share being accessed.
     * @param string $directoryPath The canonical absolute path to the directory.
     * @param string $relativePath  The directory path relative to the share root.
     *
     * @return Response The rendered directory listing.
     */
    private function renderDirectory(
        Share $share,
        string $directoryPath,
        string $relativePath,
    ): Response {
        return new TemplateResponse(
            'share/directory',
            [
                'base' => basename($directoryPath),
                'share' => $share,
                'relativePath' => $relativePath,
                'entries' => $this->listDirectory(
                    $directoryPath,
                    $relativePath,
                ),
            ]
        );
    }

    /**
     * Lists the immediate contents of a directory.
     *
     * The returned paths are relative URL paths within the share and are
     * not filesystem paths.
     *
     * @param string $directoryPath The canonical absolute directory path.
     * @param string $relativePath  The directory path relative to the share root.
     *
     * @return list<array{
     *     name: string,
     *     isDir: bool,
     *     relativePath: string
     * }>
     */
    private function listDirectory(
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

    /**
     * Serves a file using its detected MIME type.
     *
     * @param string $path The canonical absolute path to the file.
     *
     * @return Response The file response.
     */
    private function serveFile(string $path): Response
    {
        $contentType = mime_content_type($path);

        return new FileResponse(
            $path,
            $contentType !== false
                ? $contentType
                : 'application/octet-stream',
        );
    }

    /**
     * Returns the standard share-not-found response.
     *
     * @return Response
     */
    private function notFound(): Response
    {
        return new TemplateResponse('share/not-found', [], 404);
    }
}

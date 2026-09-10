<?php

namespace KittyShare\Controller;

use KittyShare\Filesystem\DirectoryBrowser;
use KittyShare\ConfigManager;
use KittyShare\Http\{Method, Request, Response};
use KittyShare\Http\{TemplateResponse, RedirectResponse};
use KittyShare\Model\Share;
use Override;

use function is_dir;
use function is_file;
use function rawurlencode;
use function realpath;
use function str_ends_with;
use function str_starts_with;

final class AdminController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        $session = $this->dependencies->authenticationManager->currentSession();

        if ($session === null) {
            return new RedirectResponse('/login');
        }

        $method = $request->getMethod();
        $uri = $request->getUri();

        if ($method === Method::Get && $uri === '/admin') {
            return $this->list($request);
        }

        if ($method === Method::Get && str_starts_with($uri, '/admin/browse')) {
            return $this->browse($request);
        }

        if ($method === Method::Get && $uri === '/admin/shares/new') {
            return $this->confirm($request);
        }

        if ($method === Method::Post && $uri === '/admin/shares') {
            return $this->create($request);
        }

        if ($method === Method::Get && $request->urlParam('id') !== null) {
            return $this->detail($request);
        }

        if ($method === Method::Post && str_ends_with($uri, '/revoke')) {
            return $this->revoke($request);
        }

        if ($method === Method::Post && str_ends_with($uri, '/unrevoke')) {
            return $this->unrevoke($request);
        }

        if ($method === Method::Post && str_ends_with($uri, '/delete')) {
            return $this->delete($request);
        }

        return new TemplateResponse('404', ['path' => $uri], 404);
    }

    private function list(Request $request): Response
    {
        $session = $this->dependencies->authenticationManager->currentSession();

        if ($session === null) {
            return new RedirectResponse('/login');
        }

        return new TemplateResponse(
            'Admin',
            parameters: [
                'user' => $session->user,
                'shares' => $this->dependencies->shareRepository->findByUser($session->user),
            ],
        );
    }

    private function ownedShare(Request $request): ?Share
    {
        $session = $this->dependencies->authenticationManager->currentSession();

        if ($session === null) {
            return null;
        }

        $id = $request->urlParam('id');

        if ($id === null) {
            return null;
        }

        $share = $this->dependencies->shareRepository->find($id);

        if ($share === null || $share->user->userId !== $session->user->userId) {
            return null;
        }

        return $share;
    }

    private function detail(Request $request): Response
    {
        $share = $this->ownedShare($request);

        if ($share === null) {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        return new TemplateResponse('admin/shares-detail', ['share' => $share, 'baseUrl' => ConfigManager::get()->baseUrl]);
    }

    private function revoke(Request $request): Response
    {
        $share = $this->ownedShare($request);

        if ($share === null) {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        if (!$share->isRevoked()) {
            $this->dependencies->shareRepository->save($share->revoke());
        }

        return new RedirectResponse('/admin/shares/' . rawurlencode($share->id));
    }

    private function unrevoke(Request $request): Response
    {
        $share = $this->ownedShare($request);

        if ($share === null) {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        if ($share->isRevoked()) {
            $this->dependencies->shareRepository->save($share->unrevoke());
        }

        return new RedirectResponse('/admin/shares/' . rawurlencode($share->id));
    }

    private function delete(Request $request): Response
    {
        $share = $this->ownedShare($request);

        if ($share === null) {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        $this->dependencies->shareRepository->delete($share);

        return new RedirectResponse('/admin');
    }

    private function browse(Request $request): Response
    {
        $root = DirectoryBrowser::browseRoot();
        $path = $request->urlParam('path');

        if ($path === null || $path === '') {
            $directoryPath = $root;
            $relativePath = '';
        } else {
            $resolved = DirectoryBrowser::resolvePath($root, $path);

            if ($resolved === null || !is_dir($resolved)) {
                return new TemplateResponse('404', ['path' => $request->getUri()], 404);
            }

            $directoryPath = $resolved;
            $relativePath = DirectoryBrowser::relativePath($root, $resolved);
        }

        return new TemplateResponse(
            'admin/browse',
            [
                'root' => $root,
                'directoryPath' => $directoryPath,
                'relativePath' => $relativePath,
                'entries' => DirectoryBrowser::listDirectory($directoryPath, $relativePath, ConfigManager::get()->showDotfiles),
            ]
        );
    }

    private function confirm(Request $request): Response
    {
        $root = DirectoryBrowser::browseRoot();
        $path = $request->get('path');

        if (!is_string($path) || $path === '') {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        $resolved = DirectoryBrowser::resolvePath($root, $path);

        if ($resolved === null || (!is_file($resolved) && !is_dir($resolved))) {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        return new TemplateResponse(
            'admin/shares-new',
            [
                'relativePath' => DirectoryBrowser::relativePath($root, $resolved),
                'filepath' => $resolved,
            ]
        );
    }

    private function create(Request $request): Response
    {
        $session = $this->dependencies->authenticationManager->currentSession();

        if ($session === null) {
            return new RedirectResponse('/login');
        }

        $filepath = $request->post('filepath');

        if (!is_string($filepath) || $filepath === '') {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        $root = DirectoryBrowser::browseRoot();
        $resolved = realpath($filepath);

        if (
            $resolved === false
            || !DirectoryBrowser::isWithinRoot($root, $resolved)
            || (!is_file($resolved) && !is_dir($resolved))
        ) {
            return new TemplateResponse('404', ['path' => $request->getUri()], 404);
        }

        $share = $this->dependencies->shareRepository->create($session->user, $resolved);

        return new RedirectResponse('/admin/shares/' . rawurlencode($share->id));
    }
}

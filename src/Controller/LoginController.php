<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response, Method};
use KittyShare\Http\{TemplateResponse};
use KittyShare\Http\Session;
use KittyShare\Manager\ConfigManager;
use Override;

final class LoginController extends BaseController
{
    private static string $errorsKey = 'login.form.errors';
    private static string $valuesKey = 'login.form.values';

    #[Override]
    public function handle(Request $request): Response
    {
        if ($this->dependencies->setupRepository->setupRequired()) {
            return $this->redirect('/setup');
        }

        if ($this->dependencies->authenticationManager->currentSession() !== null) {
            return $this->redirect('/admin');
        }

        return match ($request->getMethod()) {
            Method::Get => $this->handleGet(),
            Method::Post => $this->handlePost($request),
            default => $this->redirect('/login'),
        };
    }

    private function handleGet(): Response
    {
        $errors = Session::pull(self::$errorsKey, []);
        $values = Session::pull(self::$valuesKey, []);

        return new TemplateResponse(
            'auth/login',
            parameters: [
                'errors' => $errors,
                'values' => $values,
                'baseUrl' => ConfigManager::get()->baseUrl,
            ],
        );
    }

    private function handlePost(Request $request): Response
    {
        $username = trim($request->post('username') ?? '');
        $password = $request->post('password') ?? '';

        $errors = [];

        if ($username === '') {
            $errors['username'] = 'empty';
        }

        if ($password === '') {
            $errors['password'] = 'empty';
        }

        if ($errors !== []) {
            Session::set(self::$errorsKey, $errors);
            Session::set(self::$valuesKey, [
                'username' => $username,
            ]);

            return $this->redirect('/login');
        }

        $session = $this->dependencies->authenticationManager->login(
            $username,
            $password,
        );

        if ($session === null) {
            Session::set(self::$errorsKey, [
                'credentials' => 'invalid',
            ]);
            Session::set(self::$valuesKey, [
                'username' => $username,
            ]);

            return $this->redirect('/login');
        }

        return $this->redirect('/admin');
    }
}

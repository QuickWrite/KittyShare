<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response, Method};
use KittyShare\Http\{RedirectResponse, TemplateResponse};
use Override;

final class LoginController extends BaseController
{
    private static string $errorsKey = 'login.form.errors';
    private static string $valuesKey = 'login.form.values';

    #[Override]
    public function handle(Request $request): Response
    {
        if ($this->dependencies->setupRepository->setupRequired()) {
            return new RedirectResponse('/setup');
        }

        session_start();

        if ($this->dependencies->authenticationManager->currentSession() !== null) {
            return new RedirectResponse('/admin');
        }

        return match ($request->getMethod()) {
            Method::Get => $this->handleGet(),
            Method::Post => $this->handlePost($request),
            default => new RedirectResponse('/login'),
        };
    }

    private function handleGet(): Response
    {
        $errors = $_SESSION[self::$errorsKey] ?? [];
        $values = $_SESSION[self::$valuesKey] ?? [];

        unset(
            $_SESSION[self::$errorsKey],
            $_SESSION[self::$valuesKey],
        );

        return new TemplateResponse(
            'Login',
            parameters: [
                'errors' => $errors,
                'values' => $values,
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
            $_SESSION[self::$errorsKey] = $errors;
            $_SESSION[self::$valuesKey] = [
                'username' => $username,
            ];

            return new RedirectResponse('/login');
        }

        $session = $this->dependencies->authenticationManager->login(
            $username,
            $password,
        );

        if ($session === null) {
            $_SESSION[self::$errorsKey] = [
                'credentials' => 'invalid',
            ];
            $_SESSION[self::$valuesKey] = [
                'username' => $username,
            ];

            return new RedirectResponse('/login');
        }

        return new RedirectResponse('/admin');
    }
}

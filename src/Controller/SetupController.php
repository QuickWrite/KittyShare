<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Method, RedirectResponse, Request, Response, TemplateResponse};
use Override;

use function count;

class SetupController extends BaseController
{
    private static string $errorsKey = 'setup.form.errors';
    private static string $valuesKey = 'setup.form.values';

    #[Override]
    public function handle(Request $request): Response
    {
        if (!$this->dependencies->getSetupRepository()->setupRequired()) {
            return new RedirectResponse('/login');
        }

        session_start();

        return match ($request->getMethod()) {
            Method::Get => $this->handleGet($request),
            Method::Post => $this->handlePost($request),
            default => new RedirectResponse('/login'),
        };
    }

    private function handleGet(Request $request): Response
    {
        $errors = $_SESSION[self::$errorsKey] ?? [];
        $values = $_SESSION[self::$valuesKey] ?? [];

        unset($_SESSION[self::$errorsKey], $_SESSION[self::$valuesKey]);

        return new TemplateResponse(
            'Setup',
            parameters: [
                    'errors' => $errors,
                    'values' => $values,
                ],
        );
    }

    private function handlePost(Request $request): Response
    {
        $errors = [];

        $username = trim($request->post('username') ?? '');
        $password = trim($request->post('password') ?? '');

        if ($username === '') {
            $errors['username'] = 'empty';
        }

        if ($password === '') {
            $errors['password'] = 'empty';
        }

        if (count($errors) > 0) {
            $_SESSION[self::$valuesKey] = [ 'username' => $username ];
            $_SESSION[self::$errorsKey] = $errors;

            return new RedirectResponse('/setup');
        }

        // Create admin user
        $this->dependencies->getUserRepository()->createUser($username, $password);

        return new RedirectResponse('/login');
    }
}

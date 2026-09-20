<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Method, Request, Response, TemplateResponse};
use KittyShare\Http\Session;
use KittyShare\Manager\ConfigManager;
use Override;

use function count;

class SetupController extends BaseController
{
    private static string $errorsKey = 'setup.form.errors';
    private static string $valuesKey = 'setup.form.values';

    #[Override]
    public function handle(Request $request): Response
    {
        if (!$this->dependencies->setupRepository->setupRequired()) {
            return $this->redirect('/login');
        }

        return match ($request->getMethod()) {
            Method::Get => $this->handleGet($request),
            Method::Post => $this->handlePost($request),
            default => $this->redirect('/login'),
        };
    }

    private function handleGet(Request $request): Response
    {
        $errors = Session::pull(self::$errorsKey, []);
        $values = Session::pull(self::$valuesKey, []);

        return new TemplateResponse(
            'setup/form',
            parameters: [
                    'errors' => $errors,
                    'values' => $values,
                    'baseUrl' => ConfigManager::get()->baseUrl,
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
            Session::set(self::$valuesKey, [ 'username' => $username ]);
            Session::set(self::$errorsKey, $errors);

            return $this->redirect('/setup');
        }

        // Create admin user
        $this->dependencies->userRepository->createUser($username, $password);

        return $this->redirect('/login');
    }
}

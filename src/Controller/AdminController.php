<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response};
use KittyShare\Http\{TemplateResponse, RedirectResponse};
use Override;

final class AdminController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        session_start();

        $session = $this->dependencies->authenticationManager->currentSession();

        if ($session === null) {
            return new RedirectResponse('/login');
        }

        return new TemplateResponse(
            'Admin',
            parameters: [
                'user' => $session->user,
            ],
        );
    }
}

<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response};
use KittyShare\Http\RedirectResponse;
use Override;

final class LogoutController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        session_start();

        $this->dependencies->authenticationManager->logout();

        return new RedirectResponse('/login');
    }
}

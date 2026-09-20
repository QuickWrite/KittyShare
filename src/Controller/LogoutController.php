<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response};
use Override;

final class LogoutController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        $this->dependencies->authenticationManager->logout();

        return $this->redirect('/login');
    }
}

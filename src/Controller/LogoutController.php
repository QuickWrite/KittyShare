<?php

namespace KittyShare\Controller;

use KittyShare\Http\{Request, Response};
use KittyShare\Http\Session;
use Override;

final class LogoutController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        $this->dependencies->authenticationManager->logout();

        // The old PHP session ID was tied to an authenticated session and
        // must not stay valid after logout (fixation/replay). Rotating it
        // keeps anonymous data (e.g. flash messages) while invalidating
        // the previous identifier.
        Session::regenerate();

        return $this->redirect('/login');
    }
}

<?php

namespace KittyShare\Controller;

use KittyShare\Http\Request;
use KittyShare\Http\Response;
use Override;

final class AdminController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        throw new \Exception('Not implemented');
    }
}

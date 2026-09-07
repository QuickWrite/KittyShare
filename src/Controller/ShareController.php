<?php

namespace KittyShare\Controller;

use KittyShare\Http\Request;
use KittyShare\Http\Response;
use Override;

final class ShareController extends BaseController
{
    #[Override]
    public function handle(Request $request): Response
    {
        $id = $request->urlParam('id');
        $path = $request->urlParam('path', null);

        throw new \Exception('Not implemented');
    }
}

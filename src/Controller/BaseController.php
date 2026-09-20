<?php

namespace KittyShare\Controller;

use KittyShare\Model\Dependencies;
use KittyShare\Manager\ConfigManager;
use KittyShare\Http\{Request, Response, RedirectResponse};

abstract class BaseController
{
    public function __construct(
        protected Dependencies $dependencies,
    ) {
    }

    abstract public function handle(Request $request): Response;

    /**
     * Creates a redirect response that contains the base url.
     *
     * @param string $path The path the user should be redirected to (should not contain the base url)
     * @return RedirectResponse The redirect response
     */
    protected function redirect(string $path): RedirectResponse
    {
        return new RedirectResponse(ConfigManager::get()->baseUrl . $path);
    }
}

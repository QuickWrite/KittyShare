<?php

namespace KittyShare\Controller;

use KittyShare\Dependencies;
use KittyShare\Http\Request;
use KittyShare\Http\Response;

abstract class BaseController
{
    public function __construct(
        protected Dependencies $dependencies,
    ) {
    }

    abstract public function handle(Request $request): Response;
}

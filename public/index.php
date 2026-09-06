<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KittyShare\DependencyManager;
use KittyShare\Http\{Router, Method};

$dependencies = DependencyManager::get();

$router = new Router($dependencies);




$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$method = Method::tryFrom($_SERVER['REQUEST_METHOD']);

if ($method === null) {
    http_response_code(405);
    exit;
}

$router->dispatch($method, $path)->send();

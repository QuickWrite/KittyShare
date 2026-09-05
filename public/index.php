<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KittyShare\Http\{Router, Method};

$router = new Router();

$router->get('/', function () {
    echo 'Homepage';
});

$router->get('/login', function () {
    echo 'Login page';
});

$router->get('/setup', function () {
    echo 'Setup page';
});

$router->post('/login', function () {
    echo 'POST login page';
});

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$method = Method::tryFrom($_SERVER['REQUEST_METHOD']);

if ($method === null) {
    http_response_code(405);
    exit;
}

$router->dispatch($method, $path);

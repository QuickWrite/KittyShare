<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KittyShare\Controller\{AdminController, LoginController, LogoutController, SetupController, ShareController};
use KittyShare\DependencyManager;
use KittyShare\Http\{Router, Method};

$dependencies = DependencyManager::get();

$router = new Router($dependencies);

// Logging into the admin page
$router->get('/login', LoginController::class);
$router->post('/login', LoginController::class);

// Logging out of the application
$router->post('/logout', LogoutController::class);

// The admin page that allows for the creation of links
$router->get('/admin', AdminController::class);
$router->post('/admin', AdminController::class);

// The admin file picker for creating shares
$router->get('/admin/browse', AdminController::class);
$router->get('/admin/browse/{...path}', AdminController::class);

// Managing and creating shares
$router->get('/admin/shares/new', AdminController::class);
$router->get('/admin/shares/{id}', AdminController::class);

$router->post('/admin/shares', AdminController::class);
$router->post('/admin/shares/{id}/revoke', AdminController::class);
$router->post('/admin/shares/{id}/unrevoke', AdminController::class);
$router->post('/admin/shares/{id}/delete', AdminController::class);

// The setup page to create the first admin account
$router->get('/setup', SetupController::class);
$router->post('/setup', SetupController::class);

// The actual link sharing endpoints
$router->get('/share/{id}', ShareController::class);
$router->get('/share/{id}/{...path}', ShareController::class);

/**
 * @var array{
 *  'REQUEST_URI': string,
 *  'REQUEST_METHOD': string,
 * } $_SERVER
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$method = Method::tryFrom($_SERVER['REQUEST_METHOD']);

if ($method === null) {
    http_response_code(405);
    exit;
}

if (!is_string($path)) {
    http_response_code(500);
    exit;
}

$router->dispatch($method, $path)->send();

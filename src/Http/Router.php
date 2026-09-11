<?php

namespace KittyShare\Http;

use KittyShare\Dependencies;

/**
 * The router is responsible for dispatching requests to the correct handler
 * based on the HTTP method and path.
 */
final class Router
{
    /**
     * @var array<string, array<string, string>> $routes
     */
    private array $routes = [];

    public function __construct(
        private Dependencies $dependencies,
    ) {
    }

    public function get(string $path, string $controllerName): void
    {
        $this->add(Method::Get, $path, $controllerName);
    }

    public function post(string $path, string $controllerName): void
    {
        $this->add(Method::Post, $path, $controllerName);
    }

    public function put(string $path, string $controllerName): void
    {
        $this->add(Method::Put, $path, $controllerName);
    }

    public function delete(string $path, string $controllerName): void
    {
        $this->add(Method::Delete, $path, $controllerName);
    }

    /**
     * Adds a route to the router.
     *
     * @param Method $method         The HTTP method of the route.
     * @param string $path           The path of the route.
     * @param string $controllerName The name of the controller that should be called.
     */
    public function add(
        Method $method,
        string $path,
        string $controllerName,
    ): void {
        $path = $this->normalizePath($path);

        $this->routes[$method->value][$path] = $controllerName;
    }

    /**
     * Dispatches a request to the correct handler based on the HTTP method and path.
     *
     * @param Method $method The HTTP method of the request.
     * @param string $path   The path of the request.
     * @return Response      A response object to render
     */
    public function dispatch(Method $method, string $path): Response
    {
        $path = $this->normalizePath($path);

        foreach ($this->routes[$method->value] ?? [] as $pattern => $controllerName) {
            $parameters = $this->match($pattern, $path);

            if ($parameters === null) {
                continue;
            }

            /**
             * @var \KittyShare\Controller\BaseController $controller
             */
            $controller = new $controllerName($this->dependencies);

            return $controller->handle(
                new Request(
                    method: $method,
                    uri: $path,
                    parameters: $parameters,
                )
            );
        }

        return new TemplateResponse(
            template: "404",
            parameters: [
                'path' => $path,
            ],
            statusCode: 404,
        );
    }

    /**
     * Matches a path against a pattern and returns the captured parameters.
     *
     * @param string $pattern              The pattern to match against, e.g. /users/{id}
     * @param string $path                 The path to match, e.g. /users/123
     * @return array<string, string>|null  An array of captured parameters if the path matches the pattern,
     *                                     or null if it does not match.
     */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace_callback(
            '/\{(\.\.\.)?([^}]+)\}/',
            static function (array $match): string {
                $isCatchAll = $match[1] === '...';
                $name = $match[2];

                return $isCatchAll
                    ? '(?P<' . $name . '>.*)'
                    : '(?P<' . $name . '>[^/]+)';
            },
            $pattern,
        );

        if ($regex === null) {
            return null;
        }

        if (preg_match('#^' . $regex . '$#', $path, $matches) !== 1) {
            return null;
        }

        $parameters = array_intersect_key(
            $matches,
            array_flip(array_filter(array_keys($matches), 'is_string')),
        );

        // Templates encode path segments with rawurlencode, so decode them
        // here. rawurldecode (not urldecode) is required so that a literal
        // "+" in a file name is preserved instead of becoming a space.
        foreach ($parameters as $key => $value) {
            $parameters[$key] = rawurldecode($value);
        }

        return $parameters;
    }

    /**
     * Normalizes a path by removing trailing slashes.
     *
     * The root path "/" is preserved unchanged.
     *
     * @param string $path The path to normalize.
     * @return string      The normalized path.
     */
    private function normalizePath(string $path): string
    {
        if ($path === '/') {
            return $path;
        }

        return rtrim($path, '/');
    }
}

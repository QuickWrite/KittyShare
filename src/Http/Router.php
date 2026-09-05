<?php

namespace KittyShare\Http;

/**
 * The router is responsible for dispatching requests to the correct handler
 * based on the HTTP method and path.
 */
final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add(Method::Get, $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add(Method::Post, $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add(Method::Put, $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add(Method::Delete, $path, $handler);
    }

    /**
     * Adds a route to the router.
     *
     * @param Method $method The HTTP method of the route.
     * @param string $path The path of the route.
     * @param callable $handler The handler for the route.
     */
    public function add(
        Method $method,
        string $path,
        callable $handler,
    ): void {
        $this->routes[$method->value][$path] = $handler;
    }

    /**
     * Dispatches a request to the correct handler based on the HTTP method and path.
     *
     * @param Method $method The HTTP method of the request.
     * @param string $path The path of the request.
     * @return mixed The result of the handler, or null if no handler was found.
     */
    public function dispatch(Method $method, string $path): mixed
    {
        foreach ($this->routes[$method->value] ?? [] as $pattern => $handler) {
            $parameters = $this->match($pattern, $path);

            if ($parameters !== null) {
                return $handler(...$parameters);
            }
        }

        http_response_code(404);

        // TODO: Add real 404 page
        echo "Could not resolve the path: $path";

        return null;
    }

    /**
     * Matches a path against a pattern and returns the captured parameters.
     *
     * @param string $pattern The pattern to match against, e.g. /users/{id}
     * @param string $path The path to match, e.g. /users/123
     * @return array|null An array of captured parameters if the path matches the pattern,
     *                    or null if it does not match.
     */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace_callback(
            '#\{([^}]+)\}#',
            static function (array $match): string {
                $parameter = $match[1];

                if (str_ends_with($parameter, '...')) {
                    return '(.*)';
                }

                return '([^/]+)';
            },
            $pattern,
        );

        if ($regex === null) {
            return null;
        }

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        array_shift($matches);

        return $matches;
    }
}

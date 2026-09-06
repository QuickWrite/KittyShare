<?php

namespace KittyShare\Http;

/**
 * HTTP Request object
 */
class Request
{
    private string $method;
    private string $uri;

    private array $parameters;

    public function __construct(array $parameters)
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $this->parameters = $parameters;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParams(): array
    {
        return $_GET;
    }

    public function getUrlParams(): array
    {
        return $this->parameters;
    }

    public function urlParam(string $key, ?string $default = null): ?string
    {
        return $this->parameters[$key] ?? $default;
    }

    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}

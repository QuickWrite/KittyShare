<?php

namespace KittyShare\Http;

/**
 * HTTP Request object
 */
class Request
{
    public function __construct(
        private Method $method,
        private string $uri,
        private array $parameters,
    ) {
    }

    public function getMethod(): Method
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

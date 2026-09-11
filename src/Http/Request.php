<?php

namespace KittyShare\Http;

/**
 * HTTP Request object
 */
class Request
{
    /**
     * @param Method                $method
     * @param string                $uri
     * @param array<string, string> $parameters
     */
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

    /**
     * @return array<string, string>
     */
    public function getUrlParams(): array
    {
        return $this->parameters;
    }

    public function urlParam(string $key, ?string $default = null): ?string
    {
        return $this->parameters[$key] ?? $default;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        // I am just assuming the $_GET is an array of strings to strings. Which is not the case.
        if (!is_string($_GET[$key])) {
            return $default;
        }

        /**
         * @var array<string, string> $_POST
         */
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, ?string $default = null): ?string
    {
        // I am just assuming the $_POST is an array of strings to strings. Which is not the case.
        if (!is_string($_POST[$key])) {
            return $default;
        }

        /**
         * @var array<string, string> $_POST
         */
        return $_POST[$key] ?? $default;
    }
}

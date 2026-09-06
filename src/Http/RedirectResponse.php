<?php

namespace KittyShare\Http;

/**
 * A response that redirects the client to another URL.
 */
final class RedirectResponse implements Response
{
    /**
     * @param string $url     The URL to redirect the client to.
     * @param int $statusCode The HTTP redirect status code to send.
     */
    public function __construct(
        private string $url,
        private int $statusCode = 302,
    ) {
    }

    /**
     * Sends the redirect response to the client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);
        header("Location: {$this->url}");
    }
}

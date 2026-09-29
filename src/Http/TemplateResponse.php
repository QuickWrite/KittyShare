<?php

namespace KittyShare\Http;

use InvalidArgumentException;
use Override;

/**
 * A response that renders a PHP template.
 */
final class TemplateResponse implements Response
{
    private static string $template_dir = __DIR__ . '/../Template/';

    /**
     * @param string               $template   The name of the template to render.
     * @param array<string, mixed> $parameters Variables to make available to the template.
     * @param int                  $statusCode The HTTP status code to send.
     * @param array<string>        $headers    Additional HTTP response headers.
     */
    public function __construct(
        private string $template,
        private array $parameters = [],
        private int $statusCode = 200,
        private array $headers = [],
    ) {
        if ($template === '') {
            throw new InvalidArgumentException('Template name must not be empty.');
        }

        foreach (explode('/', $template) as $segment) {
            if (preg_match('/^[A-Za-z0-9_-]+$/', $segment) !== 1) {
                throw new InvalidArgumentException(
                    "Invalid template name '{$template}'.",
                );
            }
        }
    }

    /**
     * Renders the template and sends the response to the client.
     */
    #[Override]
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        extract($this->parameters);

        require self::$template_dir . $this->template . '.php';
    }
}

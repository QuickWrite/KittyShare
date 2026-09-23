<?php

namespace KittyShare\Http;

/**
 * A response that delegates the file transfer to Apache via mod_xsendfile.
 */
final class XSendfileResponse implements Response
{
    /**
     * @param string       $file        The canonical absolute path to send.
     * @param string       $contentType The MIME type of the file.
     * @param positive-int $statusCode  The HTTP status code to send.
     */
    public function __construct(
        private string $file,
        private string $contentType,
        private int $statusCode = 200,
    ) {
    }

    public function send(): void
    {
        if (!is_file($this->file)) {
            http_response_code(404);

            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        header("Content-Type: {$this->contentType}");

        // Prevent MIME sniffing and script execution when the browser
        // renders a shared HTML/SVG file inline.
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: sandbox');

        header('X-Sendfile: ' . $this->file);
    }
}

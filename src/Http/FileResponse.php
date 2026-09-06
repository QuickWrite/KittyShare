<?php

namespace KittyShare\Http;

/**
 * A response that sends a file to the client.
 */
final class FileResponse implements Response
{
    /**
     * @param string $file        The path to the file to send.
     * @param string $contentType The MIME type of the file.
     * @param int    $statusCode  The HTTP status code to send.
     */
    public function __construct(
        private string $file,
        private string $contentType,
        private int $statusCode = 200,
    ) {
    }

    /**
     * Sends the file to the client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        header("Content-Type: {$this->contentType}");
        header('Content-Length: ' . filesize($this->file));

        readfile($this->file);
    }
}

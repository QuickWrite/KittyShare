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
     * @param int    $chunkSize   The number of bytes sent per chunk.
     */
    public function __construct(
        private string $file,
        private string $contentType,
        private int $statusCode = 200,
        private int $chunkSize = 8192,
    ) {
    }

    /**
     * Sends the file to the client.
     *
     * The file is streamed in small chunks so that files larger than the
     * PHP memory limit can be sent.
     */
    public function send(): void
    {
        $handle = @fopen($this->file, 'rb');

        if ($handle === false) {
            http_response_code(404);

            return;
        }

        $stat = fstat($handle);
        $size = $stat !== false ? $stat['size'] : false;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (headers_sent()) {
            fclose($handle);

            return;
        }

        http_response_code($this->statusCode);

        header("Content-Type: {$this->contentType}");

        if ($size !== false) {
            header('Content-Length: ' . $size);
        }

        // Prevent MIME sniffing and script execution when the browser
        // renders a shared HTML/SVG file inline.
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: sandbox');

        while (!feof($handle)) {
            $chunk = fread($handle, $this->chunkSize);

            if ($chunk === false) {
                break;
            }

            echo $chunk;
            flush();
        }

        fclose($handle);
    }
}

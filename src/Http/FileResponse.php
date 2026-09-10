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

        http_response_code($this->statusCode);

        header("Content-Type: {$this->contentType}");

        if ($size !== false) {
            header('Content-Length: ' . $size);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);

            if ($chunk === false) {
                break;
            }

            echo $chunk;
            flush();
        }

        fclose($handle);
    }
}

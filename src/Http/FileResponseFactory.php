<?php

namespace KittyShare\Http;

use KittyShare\Manager\ConfigManager;

/**
 * Creates the configured file-download response.
 */
final class FileResponseFactory
{
    /**
     * @param string       $file        The canonical absolute path to send.
     * @param string       $contentType The MIME type of the file.
     * @param positive-int $statusCode  The HTTP status code to send.
     * @param positive-int $chunkSize   Bytes per chunk for PHP streaming.
     */
    public static function forFile(
        string $file,
        string $contentType,
        int $statusCode = 200,
        int $chunkSize = 8192,
    ): Response {
        if (ConfigManager::get()->fileServer === FileServerType::XSendfile) {
            return new XSendfileResponse($file, $contentType, $statusCode);
        }

        return new FileResponse($file, $contentType, $statusCode, $chunkSize);
    }
}

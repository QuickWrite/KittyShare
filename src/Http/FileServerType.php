<?php

namespace KittyShare\Http;

/**
 * Selectable backend used to send shared files to the client.
 */
enum FileServerType: string
{
    /** Php streams the file through the PHP process (works everywhere). */
    case Php = 'php';

    /**
     * XSendfile delegates the transfer to Apache via mod_xsendfile
     * (`X-Sendfile` header).
     */
    case XSendfile = 'x-sendfile';
}

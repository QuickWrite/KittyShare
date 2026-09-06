<?php

namespace KittyShare\Http;

/**
 * Represents an HTTP response that can be sent to the client.
 */
interface Response
{
    /**
     * Sends the response to the client.
     */
    public function send(): void;
}

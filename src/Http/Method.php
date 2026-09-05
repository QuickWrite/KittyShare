<?php

namespace KittyShare\Http;

/**
 * Reduced amount of HTTP methods from
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods.
 */
enum Method: string
{
    case Get     = 'GET';
    case Head    = 'HEAD';
    case Post    = 'POST';
    case Put     = 'PUT';
    case Delete  = 'DELETE';
    case Options = 'OPTIONS';
    case Patch   = 'PATCH';
}

<?php

/**
 * @param string                 $field The name of the field that should be printed.
 * @param 'empty'|'invalid'|null $error The error that should be printed.
 */
function print_error(string $field, ?string $error): string
{
    if ($error === null) {
        return '';
    }

    $message = match ($error) {
        'empty' => "$field cannot be empty.",
        'invalid' => "The $field is invalid. Please try again.",
    };

    return "<span class=\"error\">$message</span>";
}

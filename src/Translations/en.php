<?php

use KittyShare\Translations\TranslationKeys;

return [
    TranslationKeys::KITTYSHARE_LOGO->value   => 'KittyShare Logo',
    TranslationKeys::PAGE_404_TITLE->value    => '404 Page not found',
    TranslationKeys::PAGE_404_SUBTITLE->value => 'Hey! It seems like the site that you\'ve tried to access does not exist.',
    TranslationKeys::PAGE_404_PATHDESC->value => fn(string $path) => "Your requested path was \"$path\".",
];

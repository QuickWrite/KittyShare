<?php

namespace KittyShare\Manager;

use KittyShare\Translations\Language;
use KittyShare\Translations\TranslationKeys;

/**
 * Manages the translation values the application uses
 */
final class TranslationManager
{
    /**
     * @var array<string, pure-callable(string...): string|string> The key-value store for the translations
     */
    private readonly array $translations;

    public function __construct(Language $language)
    {
        /** @var array<string, pure-callable(string...): string|string> $translations */
        $translations = require __DIR__ . '/../Translations/' . $language->value . '.php';

        $this->translations = $translations;
    }

    public function get(TranslationKeys $key, string ...$values): string
    {
        // If the key does not exist, something went wrong... badly.
        $translation = $this->translations[$key->value];

        if (is_string($translation)) {
            return $translation;
        }

        return $translation(...$values);
    }
}

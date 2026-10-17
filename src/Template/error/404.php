<?php
use KittyShare\Manager\TranslationManager;
use KittyShare\Translations\TranslationKeys;
/**
 * @var string $path
 * @var TranslationManager $translationManager
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

$GLOBALS['__kittyshare_base'] = $baseUrl ?? '';

renderHeader($translationManager->get(TranslationKeys::PAGE_404_TITLE));
?>
<main>
    <h1 class="title"><?= $translationManager->get(TranslationKeys::PAGE_404_TITLE) ?></h1>
    <p><?= $translationManager->get(TranslationKeys::PAGE_404_SUBTITLE) ?></p>

    <p class="margin-top-6"><?= e($translationManager->get(TranslationKeys::PAGE_404_PATHDESC, $path)) ?></p>
</main>
<?php
renderFooter();

<?php
/**
 * @var string $root
 * @var string $directoryPath
 * @var string $relativePath
 * @var string $baseUrl
 * @var list<array{
 *     name: string,
 *     isDir: bool,
 *     relativePath: string
 * }> $entries
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

$GLOBALS['__kittyshare_base'] = $baseUrl;
renderHeader("Select path for new share");
?>
<main>
    <h1 class="title">Select path for new share</h1>

    <p>Browsing: <?= e($directoryPath) ?></p>

    <p class="margin-top-3 margin-bottom-3">
        <a href="<?= l('/admin/shares/new?path=' . rawurlencode($relativePath === '' ? '/' : $relativePath)) ?>">Select this folder</a>
    </p>

    <?php
    $entryBaseUrl = $baseUrl . '/admin/browse';
    $backUrl = '/admin/browse';
    $selectBaseUrl = l('/admin/shares/new');
    require __DIR__ . '/../partials/directory-list.php';
    ?>

    <p class="margin-top-3">
        <a href="<?= l('/admin') ?>">Back to admin</a>
    </p>
</main>
<?php
renderFooter();

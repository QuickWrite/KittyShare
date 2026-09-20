<?php
/**
 * @var \KittyShare\Model\Share $share
 * @var string $base
 * @var string $baseUrl
 * @var string $relativePath
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

$shareUrl = '/share/' . rawurlencode((string) $share->id);

renderHeader("Shared directory " . $base);
?>
<main>
    <h1 class="title">Share of <?= e($base) ?></h1>

    <?php
    $entryBaseUrl = l($shareUrl);
    $backUrl = $shareUrl;
    $selectBaseUrl = null;
    require __DIR__ . '/../partials/directory-list.php';
    ?>
</main>
<?php
renderFooter();

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

if (\KittyShare\Manager\ConfigManager::get()->metaOgMode !== 'none') {
    $fileCount = 0;
    $dirCount = 0;
    foreach ($entries as $entry) {
        if ($entry['isDir']) {
            $dirCount++;
        } else {
            $fileCount++;
        }
    }

    if ($fileCount === 0 && $dirCount === 0) {
        $ogDescription = 'Empty folder';
    } elseif ($dirCount === 0) {
        $ogDescription = $fileCount === 1 ? '1 file' : $fileCount . ' files';
    } elseif ($fileCount === 0) {
        $ogDescription = $dirCount === 1 ? '1 folder' : $dirCount . ' folders';
    } else {
        $ogDescription = ($fileCount === 1 ? '1 file' : $fileCount . ' files')
            . ', '
            . ($dirCount === 1 ? '1 folder' : $dirCount . ' folders');
    }

    $ogUrl = null;

    renderHeader("Shared directory " . $base, [
        'ogTitle' => $base . ' ~ shared via KittyShare',
        'ogDescription' => $ogDescription,
        'ogUrl' => $ogUrl,
    ]);
} else {
    renderHeader("Shared directory " . $base);
}
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

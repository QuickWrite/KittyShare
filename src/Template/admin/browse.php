<?php
/**
 * @var string $root
 * @var string $directoryPath
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

renderHeader("Select path for new share");
?>
<main>
    <h1>Select path for new share</h1>

    <p>Browsing: <?= e($directoryPath) ?></p>

    <p>
        <a href="<?= e(
            '/admin/shares/new?path=' . rawurlencode($relativePath === '' ? '/' : $relativePath)
        ) ?>">Select this folder</a>
    </p>

    <?php
    $baseUrl = '/admin/browse';
    $backUrl = '/admin/browse';
    $selectBaseUrl = '/admin/shares/new';
    require __DIR__ . '/../partials/directory-list.php';
    ?>

    <p>
        <a href="/admin">Back to admin</a>
    </p>
</main>
<?php
renderFooter();

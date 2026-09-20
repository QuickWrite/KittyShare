<?php
/**
 * @var string $relativePath
 * @var string $filepath
 * @var string $baseUrl
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

$GLOBALS['__kittyshare_base'] = $baseUrl;

$parent = dirname($relativePath);
$browseUrl = '/admin/browse';
if ($parent !== '' && $parent !== '.') {
    $browseUrl .= '/' . implode(
        '/',
        array_map('rawurlencode', explode('/', $parent)),
    );
}

renderHeader("Create share");
?>
<main>
    <h1 class="title">Create share</h1>

    <p>Selected path: <code class="path"><?= e($filepath) ?></code></p>

    <p class="margin-top-3">Do you want to create a share for this path?</p>

    <form method="post" action="<?= l('/admin/shares') ?>" class="margin-top-3 margin-bottom-3">
        <input type="hidden" name="filepath" value="<?= e($filepath) ?>" />
        <button type="submit" class="button">Create share</button>
    </form>

    <p class="margin-top-3">
        <a href="<?= l(e($browseUrl)) ?>">Cancel (back to file tree)</a>
    </p>

    <p class="margin-top-3">
        <a href="<?= l('/admin') ?>">Back to admin</a>
    </p>
</main>
<?php
renderFooter();

<?php
/**
 * @var string $relativePath
 * @var string $filepath
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

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
    <h1>Create share</h1>

    <p>Selected path: <?= e($filepath) ?></p>

    <p>Do you want to create a share for this path?</p>

    <form method="post" action="/admin/shares">
        <input type="hidden" name="filepath" value="<?= e($filepath) ?>" />
        <button type="submit">Create share</button>
    </form>

    <p>
        <a href="<?= e($browseUrl) ?>">Cancel (back to file tree)</a>
    </p>

    <p>
        <a href="/admin">Back to admin</a>
    </p>
</main>
<?php
renderFooter();

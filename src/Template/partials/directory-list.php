<?php
/**
 * Shared directory listing
 *
 * @var string $baseUrl
 * @var list<array{
 *     name: string,
 *     isDir: bool,
 *     relativePath: string
 * }> $entries
 * @var string|null $backUrl
 * @var string|null $selectBaseUrl Optional base URL for a per-entry "Select"
 *     link (used by the admin file picker). When set, each entry links to
 *     $selectBaseUrl . '?path=' . rawurlencode(relativePath).
 */
$backUrl ??= null;
$selectBaseUrl ??= null;

if (!function_exists('encodePath')) {
    function encodePath(string $path): string
    {
        return implode(
            '/',
            array_map('rawurlencode', explode('/', $path)),
        );
    }
}
?>
<?php if ($entries !== []): ?>
    <ul class="directory-list">
        <?php foreach ($entries as $entry): ?>
            <?php
            $name = e($entry['name']);
            $path = $entry['relativePath'];

            $entryUrl = $baseUrl . '/' . encodePath($path);
            $selectUrl = $selectBaseUrl !== null
                ? $selectBaseUrl . '?path=' . rawurlencode($path)
                : null;

            $isDir = $entry['isDir'];

            $isSelectableFile = $selectUrl !== null && !$isDir;
            ?>

            <li class="directory-list--item">
                <?php if ($isDir): ?>
                    <img src="/assets/icon/folder.svg" alt="Folder">
                <?php else: ?>
                    <img src="/assets/icon/file.svg" alt="File">
                <?php endif; ?>

                <?php if ($isSelectableFile): ?>
                    <?= $name ?>
                <?php else: ?>
                    <a href="<?= e($entryUrl) ?>">
                        <?= $name ?><?=  $isDir ? '/' : '' ?>
                    </a>
                <?php endif; ?>

                <?php if ($selectUrl !== null): ?>
                    (<a href="<?= e($selectUrl) ?>">Select</a>)
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="margin-top-6">This directory is empty.</p>
<?php endif; ?>

<?php if ($backUrl !== null): ?>
    <p class="margin-top-6">
        <a href="<?= e($backUrl) ?>">Back to root</a>
    </p>
<?php endif; ?>

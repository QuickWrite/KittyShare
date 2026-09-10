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
    <ul>
        <?php foreach ($entries as $entry): ?>
            <li>
                <?php if ($selectBaseUrl !== null && !$entry['isDir']): ?>
                    <?= e($entry['name']) ?>
                    (<a href="<?= e(
                        $selectBaseUrl . '?path=' . rawurlencode($entry['relativePath'])
                    ) ?>">Select</a>)
                <?php else: ?>
                    <a href="<?= e(
                        $baseUrl . '/' . encodePath($entry['relativePath'])
                    ) ?>">
                        <?= e($entry['name']) ?><?= $entry['isDir'] ? '/' : '' ?>
                    </a>
                    <?php if ($selectBaseUrl !== null): ?>
                        (<a href="<?= e(
                            $selectBaseUrl . '?path=' . rawurlencode($entry['relativePath'])
                        ) ?>">Select</a>)
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>This directory is empty.</p>
<?php endif; ?>

<?php if ($backUrl !== null): ?>
    <p>
        <a href="<?= e($backUrl) ?>">Back to root</a>
    </p>
<?php endif; ?>

<?php
/**
 * @var \KittyShare\Model\Share $share
 * @var string $base
 * @var string $relativePath
 * @var list<array{
 *     name: string,
 *     isDir: bool,
 *     relativePath: string
 * }> $entries
 */
?>
<?php
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
}

function encodePath(string $path): string
{
    return implode(
        '/',
        array_map('rawurlencode', explode('/', $path)),
    );
}

$shareUrl = '/share/' . rawurlencode((string) $share->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared directory <?= e($base) ?> | KittyShare</title>
</head>
<body>
<main>
    <h1>Share of <?= e($base) ?></h1>

    <?php if ($entries !== []): ?>
        <ul>
            <?php foreach ($entries as $entry): ?>
                <li>
                    <a href="<?= e(
                        $shareUrl . '/' . encodePath($entry['relativePath'])
                    ) ?>">
                        <?= e($entry['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>This directory is empty.</p>
    <?php endif; ?>

    <p>
        <a href="<?= e($shareUrl) ?>">Back to root</a>
    </p>
</main>
</body>
</html>

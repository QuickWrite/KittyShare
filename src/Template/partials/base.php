<?php

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
}

function l(string $link): string {
    /** @var string|null */
    $prefix = $GLOBALS['__kittyshare_base'] ?? null;

    if ($prefix === null || $prefix === '') {
        return $link;
    }

    return $prefix . $link;
}

function renderHeader(string $title): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | KittyShare</title>

    <link rel="icon" type="image/png" href="<?= l('/assets/logo/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= l('/assets/logo/kittyshare-logo-light.svg') ?>" />

    <link rel="stylesheet" href="<?= l('/assets/style.css') ?>">
</head>
<body>
    <header class="header">
        <div class="header--icon">
            <img src="<?= l('/assets/logo/kittyshare-logo-light.svg') ?>" alt="" /> <span>KittyShare</span>
        </div>
    </header>
<?php
}

function renderFooter(): void {
?>
    <footer class="footer">
        <span class="footer--note">
            Powered by <a href="https://github.com/QuickWrite/KittyShare">KittyShare</a>.
        </span>
    </footer>
</body>
</html>
<?php
}

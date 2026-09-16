<?php
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
}

function renderHeader(string $title): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | KittyShare</title>

    <!-- TODO: Add base path to enable other configuations than just the root -->
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="header">
        <div class="header--icon">
            <img src="/assets/logo/kittyshare-logo-light.svg" alt="" /> <span>KittyShare</span>
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

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

/**
 * Renders the HTML head with privacy-friendly meta tags.
 *
 * @param string $title Page title.
 * @param array{
 *     description?: ?string,
 *     ogMode?: ?string,
 *     ogTitle?: ?string,
 *     ogDescription?: ?string,
 *     ogUrl?: ?string,
 *     ogImage?: ?string
 * } $options Per-page overrides. Omitted values fall back to Config where applicable.
 */
function renderHeader(string $title, array $options = []): void
{
    $config = null;

    if (array_key_exists('description', $options)) {
        $metaDescription = $options['description'];
    } else {
        $config = \KittyShare\Manager\ConfigManager::get();
        $metaDescription = $config->metaDescription;
    }

    if (array_key_exists('ogMode', $options)) {
        $ogMode = $options['ogMode'];
    } else {
        $config ??= \KittyShare\Manager\ConfigManager::get();
        $ogMode = $config->metaOgMode;
    }

    $ogTitle = $options['ogTitle'] ?? null;
    $ogDescription = $options['ogDescription'] ?? null;
    $ogUrl = $options['ogUrl'] ?? null;

    $ogImage = array_key_exists('ogImage', $options)
        ? $options['ogImage']
        : l('/assets/logo/favicon-96x96.png');

    if ($ogMode !== 'minimal' && $ogMode !== 'per-share') {
        $ogMode = 'none';
    }

    $normalize = static function (mixed $value): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    };

    $metaDescription = $normalize($metaDescription);
    $ogTitle = $normalize($ogTitle);
    $ogDescription = $normalize($ogDescription);
    $ogUrl = $normalize($ogUrl);
    $ogImage = $normalize($ogImage);

    // In 'minimal' mode, use generic OG metadata so filenames/share
    // information are not exposed.
    //
    // In 'per-share' mode, callers may provide share-specific title
    // and description. Fall back to the regular meta description.
    $resolvedOgTitle = null;
    $resolvedOgDescription = null;

    if ($ogMode === 'per-share') {
        $resolvedOgTitle = $ogTitle;
        $resolvedOgDescription = $ogDescription ?? $metaDescription;
    } elseif ($ogMode === 'minimal') {
        $resolvedOgTitle = 'KittyShare';
        $resolvedOgDescription = $metaDescription;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | KittyShare</title>

    <link rel="icon" type="image/png" href="<?= l('/assets/logo/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= l('/assets/logo/kittyshare-logo-light.svg') ?>" />

    <meta name="application-name" content="KittyShare">
    <?php if ($metaDescription !== null): ?>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php endif; ?>
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#202124">

    <?php if ($ogMode !== 'none'): ?>
    <meta property="og:site_name" content="KittyShare">
    <meta property="og:type" content="website">
        <?php if ($resolvedOgTitle !== null): ?>
    <meta property="og:title" content="<?= e($resolvedOgTitle) ?>">
        <?php endif; ?>
        <?php if ($resolvedOgDescription !== null): ?>
    <meta property="og:description" content="<?= e($resolvedOgDescription) ?>">
        <?php endif; ?>
        <?php if ($ogUrl !== null): ?>
    <meta property="og:url" content="<?= e(trim($ogUrl)) ?>">
        <?php endif; ?>
        <?php if ($ogImage !== null): ?>
    <meta property="og:image" content="<?= e(trim($ogImage)) ?>">
    <meta property="og:image:alt" content="KittyShare logo">
        <?php endif; ?>
    <?php endif; ?>

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

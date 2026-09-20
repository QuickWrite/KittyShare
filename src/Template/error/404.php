<?php
/** @var string $path */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

$GLOBALS['__kittyshare_base'] = $baseUrl ?? '';

renderHeader("404 Page not found");
?>
<main>
    <h1 class="title">404 Page not found</h1>
    <p>Hey! It seems like the site that you've tried to access does not exist.</p>

    <p class="margin-top-6">Your requested path was &quot;<?= htmlspecialchars($path) ?>&quot;.</p>
</main>
<?php
renderFooter();

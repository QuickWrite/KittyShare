<?php
/** @var string $path */
?>
<?php
require_once __DIR__ . '/partials/base.php';

renderHeader("404 Page not found");
?>
<main>
    <h1>404 Page not found</h1>
    <p>Hey! It seems like the site that you've tried to access does not exist.</p>

    <p>Your requested path was &quot;<?= htmlspecialchars($path) ?>&quot;.</p>
</main>
<?php
renderFooter();

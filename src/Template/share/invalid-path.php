<?php
/**
 * @var \KittyShare\Model\Share $share
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

renderHeader("Could not find path");
?>
<main>
    <h1>Could not find the path provided</h1>

    <p><a href="/share/<?= htmlspecialchars($share->id) ?>">Back to root</a></p>
</main>
<?php 
renderFooter();

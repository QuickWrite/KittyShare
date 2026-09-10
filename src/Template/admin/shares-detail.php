<?php
/**
 * @var \KittyShare\Model\Share $share
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

renderHeader("Manage share");
?>
<main>
    <h1>Manage share</h1>

    <p>
        Link: <a href="/share/<?= e($share->id) ?>">/share/<?= e($share->id) ?></a>
    </p>

    <p>Path: <?= e($share->filepath) ?></p>

    <p>Status:
        <?php if ($share->isRevoked()): ?>
            revoked
        <?php elseif ($share->isExpired()): ?>
            expired
        <?php else: ?>
            active
        <?php endif; ?>
    </p>

    <?php if ($share->isRevoked()): ?>
        <form method="post" action="/admin/shares/<?= e($share->id) ?>/unrevoke">
            <button type="submit">Unrevoke</button>
        </form>
    <?php else: ?>
        <form method="post" action="/admin/shares/<?= e($share->id) ?>/revoke">
            <button type="submit">Revoke</button>
        </form>
    <?php endif; ?>

    <form method="post" action="/admin/shares/<?= e($share->id) ?>/delete">
        <button type="submit">Delete</button>
    </form>

    <p>
        <a href="/admin">Back to admin</a>
    </p>
</main>
<?php
renderFooter();

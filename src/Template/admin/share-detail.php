<?php
/**
 * @var \KittyShare\Model\Share $share
 * @var string|null $baseUrl
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

$shareLink = '/share/' . rawurlencode($share->id);

if (($baseUrl ?? null) !== null && $baseUrl !== '') {
    $shareLink = $baseUrl . $shareLink;
}

renderHeader("Manage share");
?>
<main>
    <div class="left-right">
        <h1 class="title">Manage share</h1>

        <form method="post" action="/admin/shares/<?= e($share->id) ?>/delete" class="margin-top-3">
            <button type="submit" class="button bg-danger-hover">Delete</button>
        </form>
    </div>

    <p class="margin-top-3">
        <span class="infopoint">Link:</span> <a href="<?= e($shareLink) ?>"><?= e($shareLink) ?></a>
    </p>

    <p><span class="infopoint">Path:</span> <code class="path"><?= e($share->filepath) ?></code></p>

    <p><span class="infopoint">Status:</span>
        <?php if ($share->isRevoked()): ?>
            <span class="pill pill--revoked right">revoked</span>
        <?php elseif ($share->isExpired()): ?>
            <span class="pill pill--expired right">expired</pill>
        <?php else: ?>
            <span class="pill pill--active right">active</span>
        <?php endif; ?>
    </p>

    <?php if ($share->isRevoked()): ?>
        <form method="post" action="/admin/shares/<?= e($share->id) ?>/unrevoke" class="margin-top-6">
            <button type="submit" class="button bg-warning-hover">Unrevoke</button>
        </form>
    <?php else: ?>
        <form method="post" action="/admin/shares/<?= e($share->id) ?>/revoke" class="margin-top-6">
            <button type="submit" class="button bg-warning-hover">Revoke</button>
        </form>
    <?php endif; ?>

    <p class="margin-top-3">
        <a href="/admin">Back to admin</a>
    </p>
</main>
<?php
renderFooter();

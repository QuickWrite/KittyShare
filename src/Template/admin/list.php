<?php
/**
 * @var \KittyShare\Model\User $user
 * @var list<\KittyShare\Model\Share> $shares
 * @var string|null $baseUrl
 */
?>
<?php
require_once __DIR__ . '/../partials/base.php';

renderHeader("Admin");
?>
<main>
    <div class="left-right">
        <div>
            <h1 class="title">Admin</h1>

            <p>
                Welcome, <?= e($user->username) ?>!
            </p>
        </div>

        <p>
            <a href="/admin/browse" class="button">Create new share</a>
        </p>
    </div>

    <h2 class="subtitle">Your shares</h2>

    <?php if ($shares !== []): ?>
        <ul class="directory-list">
            <?php foreach ($shares as $share): ?>
                <li class="directory-list--item">
                    <?php
                        $isActive = !($share->isRevoked() || $share->isExpired());
                    ?>

                    <?php if ($share->isRevoked()): ?>
                        <span class="pill pill--revoked">revoked</span>
                    <?php elseif ($share->isExpired()): ?>
                        <span class="pill pill--expired">expired</pill>
                    <?php else: ?>
                        <span class="pill pill--active">active</span>
                    <?php endif; ?>

                    <?php if ($isActive): ?>
                    <?php
                        $shareLink = '/share/' . rawurlencode($share->id);

                        if (($baseUrl ?? null) !== null && $baseUrl !== '') {
                            $shareLink = $baseUrl . $shareLink;
                        }
                    ?>
                    <a href="<?= $shareLink ?>">
                    <?php endif; ?>
                        <?= e(basename($share->filepath) !== '' ? basename($share->filepath) : $share->filepath) ?>
                    <?php if ($isActive): ?>
                    </a>
                    <?php endif; ?>

                    <!-- TODO: Correct link with baseUrl -->
                    <span class="right"><a href="/admin/shares/<?= e($share->id) ?>">Edit</a></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No shares yet.</p>
    <?php endif; ?>

    <form method="post" action="/logout" class="margin-top-6">
        <button type="submit" class="button bg-danger-hover">Log out</button>
    </form>
</main>
<?php
renderFooter();

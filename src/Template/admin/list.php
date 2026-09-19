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
                        $shareLink = '/share/' . rawurlencode($share->id);

                        if (($baseUrl ?? null) !== null && $baseUrl !== '') {
                            $shareLink = $baseUrl . $shareLink;
                        }
                    ?>

                    <?php if (!($share->isRevoked() || $share->isExpired())): ?>
                        <!-- TODO: Add base to icon link -->
                        <a href="<?= $shareLink ?>"><img src="/assets/icon/new-tab.svg" alt="Share link to resource" class="text-icon"></a>
                    <?php else: ?>
                        <span><img src="/assets/icon/new-tab.svg" alt="Share link to resource" class="text-icon invalid"></span>
                    <?php endif; ?>

                    <a href="/admin/shares/<?= e($share->id) ?>">
                        <?= e(basename($share->filepath) !== '' ? basename($share->filepath) : $share->filepath) ?>
                    </a>
                    <?php if ($share->isRevoked()): ?>
                        <span class="pill pill--revoked right">revoked</span>
                    <?php elseif ($share->isExpired()): ?>
                        <span class="pill pill--expired right">expired</pill>
                    <?php else: ?>
                        <span class="pill pill--active right">active</span>
                    <?php endif; ?>
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

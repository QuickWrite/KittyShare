<?php
/** @var \KittyShare\Model\User $user */
/** @var list<\KittyShare\Model\Share> $shares */
?>
<?php
require_once __DIR__ . '/partials/base.php';

renderHeader("Admin");
?>
<main>
    <h1>Admin</h1>

    <p>
        Welcome, <?= e($user->username) ?>!
    </p>

    <p>
        <a href="/admin/browse">Create new share</a>
    </p>

    <h2>Your shares</h2>

    <?php if ($shares !== []): ?>
        <ul>
            <?php foreach ($shares as $share): ?>
                <li>
                    <a href="/admin/shares/<?= e($share->id) ?>">
                        <?= e(basename($share->filepath) !== '' ? basename($share->filepath) : $share->filepath) ?>
                    </a>
                    <?php if ($share->isRevoked()): ?>
                        (revoked)
                    <?php elseif ($share->isExpired()): ?>
                        (expired)
                    <?php else: ?>
                        (active)
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No shares yet.</p>
    <?php endif; ?>

    <form method="post" action="/logout">
        <button type="submit">Log out</button>
    </form>
</main>
<?php
renderFooter();

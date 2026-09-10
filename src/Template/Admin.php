<?php
/** @var \KittyShare\Model\User $user */
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

    <form method="post" action="/logout">
        <button type="submit">Log out</button>
    </form>
</main>
<?php
renderFooter();

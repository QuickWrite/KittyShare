<?php

/**
 * @var ?array{
 *        'username'?: 'empty'|'invalid',
 *        'password'?: 'empty'|'invalid',
 *        'credentials'?: 'invalid'
 * }                               $errors The errors that are currently present
 * @var ?array{'username'?: string} $values The values the user has provided before
 */

$errors ??= [];
$values ??= [];

require_once __DIR__ . '/partials/base.php';
require_once __DIR__ . '/util/error.php';

renderHeader("Login");
?>
<main>
    <h1>Login</h1>

    <form method="post">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            placeholder="John Doe"
            value="<?= e($values['username'] ?? '') ?>"
            autocomplete="username"
            required />
        <?= print_error('Username', $errors['username'] ?? null) ?>

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required />
        <?= print_error('Password', $errors['password'] ?? null) ?>

        <?php if (isset($errors['credentials'])): ?>
            <?= print_error('', $errors['credentials']) ?>
        <?php endif; ?>

        <button type="submit">Login</button>
    </form>
</main>
<?php
renderFooter();

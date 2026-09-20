<?php

/**
 * @var ?array{'username'?: 'empty', 'password'?: 'empty'} $errors The errors that are currently present
 * @var ?array{'username'?: string} $values                        The values the user has provided before
 */
$errors ??= [];
$values ??= [];

require_once __DIR__ . '/../partials/base.php';
require_once __DIR__ . '/../partials/form-error.php';

$GLOBALS['__kittyshare_base'] = $baseUrl ?? '';

renderHeader("Setup Application");
?>
<main class="content-center">
    <form method="post" class="form">
        <h1 class="title">Setup Application</h1>

        <div class="form--item">
            <label for="username">Username:</label>
            <input
                type="text"
                id="username"
                name="username"
                class="text-input"
                placeholder="John Doe"
                value="<?= e($values['username'] ?? '') ?>"
                required
            />
            <?= print_error('Username', $errors['username'] ?? null) ?>
        </div>

        <div class="form--item">
            <label for="password">Password:</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Password"
                class="text-input"
                required
            />
            <?= print_error('Password', $errors['password'] ?? null) ?>
        </div>

        <button type="submit" class="button">Create Account</button>
    </form>
</main>
<?php
renderFooter();

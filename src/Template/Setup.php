<?php

/**
 * @var ?array{'username'?: 'empty', 'password'?: 'empty'} $errors The errors that are currently present
 * @var ?array{'username'?: string} $values                        The values the user has provided before
 */
$errors ??= [];
$values ??= [];

require_once __DIR__ . '/partials/base.php';
require_once __DIR__ . '/util/error.php';

renderHeader("Setup Application");
?>
<main>
    <h1>Setup Application</h1>
    <form method="post">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="John Doe" value="<?= e($values['username'] ?? '') ?>" required />
        <?= print_error('Username', $errors['username'] ?? null) ?>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
        <?= print_error('Password', $errors['password'] ?? null) ?>

        <button type="submit">Submit</button>
    </form>
</main>
<?php
renderFooter();

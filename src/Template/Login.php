<?php
$errors ??= [];
$values ??= [];

function print_error(string $field, ?string $error): string
{
    if ($error === null) {
        return '';
    }

    $message = match ($error) {
        'empty' => "$field cannot be empty.",
        'invalid' => 'The username or password is incorrect.',
        default => "$field is invalid.",
    };

    return "<span class=\"error\">$message</span>";
}

require_once __DIR__ . '/partials/base.php';

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
            required
        />
        <?= print_error('Username', $errors['username'] ?? null) ?>

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        />
        <?= print_error('Password', $errors['password'] ?? null) ?>

        <?php if (isset($errors['credentials'])): ?>
            <?= print_error('', $errors['credentials']) ?>
        <?php endif; ?>

        <button type="submit">Login</button>
    </form>
</main>
<?php
renderFooter();

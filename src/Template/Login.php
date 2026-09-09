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

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KittyShare</title>
</head>
<body>
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
</body>
</html>

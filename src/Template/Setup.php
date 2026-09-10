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
        default => "$field is invalid"
    };

    return "<span class=\"error\">$message</span>";
}

require_once __DIR__ . '/partials/base.php';

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

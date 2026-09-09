<?php
/** @var \KittyShare\Model\User $user */
?>
<?php
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
    <title>Admin | KittyShare</title>
</head>
<body>
    <main>
        <h1>Admin</h1>

        <p>
            Welcome, <?= e($user->username) ?>!
        </p>

        <form method="post" action="/logout">
            <button type="submit">Log out</button>
        </form>
    </main>
</body>
</html>

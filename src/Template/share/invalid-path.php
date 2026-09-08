<?php
/**
 * @var \KittyShare\Model\Share $share
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Could not find path | KittyShare</title>
</head>
<body>
    <main>
        <h1>Could not find the path provided</h1>

        <p><a href="/share/<?= htmlspecialchars($share->id) ?>">Back to root</a></p>
    </main>
</body>
</html>

<?php
include '../library/sessionHandling.php';

header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Archivist</title>
    <link rel="stylesheet" href="resources/styles.css">
    <link rel="manifest" href="manifest.json"/>
</head>
<body>
<p>
    <strong>Photo Archivist</strong>
</p>
<h1>
    Wir machen den Speicher wieder groß!
</h1>
<div class="center">
    <p>Bitte logge Dich ein, um zu beginnen.</p>

    <form action="login.php" method="POST">
        <div class="form-row">
            <label for="username">Dein Nutzername:</label>
            <input name="username" type="text" required>
        </div>
        <div class="form-row">
            <label for="password">Dein Passwort:</label>
            <input name="password" type="password" autocomplete="off" required>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        </div>
        <button id="login" type="submit">Einloggen</button>
    </form>
</div>

</body>
</html>

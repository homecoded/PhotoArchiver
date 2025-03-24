<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Archivist</title>
    <link rel="stylesheet" href="resources/styles.css">
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
            <label for="username">Dein Nutzername</label>
            <input name="username" type="text">
        </div>
        <div class="form-row">
            <label for="password">Dein Passwort</label>
            <input name="password" type="text">
        </div>
        <button id="login" type="submit">Einloggen</button>
    </form>
</div>

</body>
</html>

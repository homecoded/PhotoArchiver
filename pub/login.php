<?php
declare(strict_types=1);
?>

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

<?php
$username = $_POST['username'];
$password = $_POST['password'];

$authFilePath = __DIR__ . '/../.auth.json';
if (file_exists($authFilePath)) {
    $string = file_get_contents($authFilePath);
    $authData = json_decode($string, true);
}

if (!isset($authData)) {
    echo "Authentication not setup";
    http_response_code(500);
    exit();
}

$authUserData = $authData['users'][$username] ?? [];

$salt = $authUserData['salt'] ?? 'nohash';
$hash = sha1($password.$salt);

if ($hash === ($authUserData['password'] ?? '')) {
    $token = [
        'xtoken' => $authUserData['token'],
    ];
} else {
    http_response_code(401);
    echo '<h1>Login fehlgeschlagen</h1>';
    echo '<a href="index.php">Zurück</a>';
    echo '</body></html>';
    exit();
}
?>

<h1>
    Wir loggen Dich ein
</h1>

<script>
    localStorage.setItem("xtoken", "<?= htmlspecialchars($token['xtoken']) ?>");
    window.location.href = "run.php";
</script>
</body>
</html>
<?php
declare(strict_types=1);
include '../library/sessionHandling.php';
include '../library/rateLimiter.php';
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

rate_limiter('login' . $username, 10,60);

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

if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo "Invalid CSRF-Token";
    http_response_code(403); // Forbidden
    exit();
}

if (empty($authData) || !isset($authData['users'])) {
    die('User handling not initialized');
}

$authUserData = $authData['users'][$username] ?? [];
$storedHash = $authUserData['password'] ?? 'nopassword';

if (password_verify($password, $storedHash)) {
    $token = [
        'xtoken' => $authUserData['token'],
    ];
} else {
    sleep(2 + rand(1, 5));
    http_response_code(401);
    echo '<h1>Login fehlgeschlagen</h1>';
    echo '<a href="index.php">Zurück</a>';
    echo '</body></html>';
    exit();
}

session_regenerate_id(true);
?>

<h1>
    Wir loggen Dich ein
</h1>

<script>
    const xtoken = <?= json_encode($token['xtoken'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    localStorage.setItem("xtoken", xtoken);
    window.location.href = "run.php";
</script>
</body>
</html>

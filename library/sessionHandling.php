<?php
declare(strict_types=1);

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

session_set_cookie_params([
    'httponly' => true,
    'secure' => $isSecure,
    'samesite' => 'Strict',
    'path' => '/'
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$nonce = sha1(bin2hex(random_bytes(24)));

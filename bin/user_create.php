<?php
declare(strict_types=1);

if (!isset($argv[1])) {
    echo "Please specify username and password\n";
    echo "./bin/user_create.php user=username password=password\n";
    exit(2);
}

// load auth file
$authFilePath = __DIR__ . '/../.auth.json';
if (file_exists($authFilePath)) {
    $string = file_get_contents($authFilePath);
    $authData = json_decode($string, true);
} else {
    $authData = [
        'users' => []
    ];
}

$newUserData = [];

foreach ($argv as $arg) {
    $data = explode('=', $arg, 2);
    if (count($data) > 1) {
        $newUserData[$data[0]] = $data[1];
    }
}

if (!isset($newUserData['username']) || !isset($newUserData['password'])) {
    echo "Please specify username and password\n";
    echo "./bin/user_create.php user=username password=password\n";
    exit(2);
}

$userAuth = [
    'password' => password_hash($newUserData['password'], PASSWORD_DEFAULT),
    'token' => bin2hex(random_bytes(24))
];

$authData['users'][$newUserData['username']] = $userAuth;

file_put_contents($authFilePath, json_encode($authData, JSON_PRETTY_PRINT));


<?php
declare(strict_types=1);

function rate_limiter($key, $limit, $period) {
    $filename = sys_get_temp_dir() . '/RLIMITER_' . hash('sha256', $key) . '.json';

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim(end($forwardedIps));
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
        die('Error: Invalid IP address');
    }

    $rateContent = file_get_contents($filename);
    if (empty($rateContent)) {
        $data = [];
    } else {
        $data = json_decode($rateContent, true) ?: [];
    }


    $current_time = time();
    if (!isset($data[$ip])) {
        $data[$ip] = ['count' => 0, 'last_access_time' => $current_time];
    }

    if ($current_time - $data[$ip]['last_access_time'] >= $period) {
        $data[$ip]['count'] = 0;
    }

    if ($data[$ip]['count'] >= $limit) {
        http_response_code(429);
        header('Retry-After: ' . $period);
        die('Error: Rate limit exceeded');
    }

    $data[$ip]['count']++;
    $data[$ip]['last_access_time'] = $current_time;

    file_put_contents($filename, json_encode($data), LOCK_EX);
    return $period - ($current_time - $data[$ip]['last_access_time']);
}

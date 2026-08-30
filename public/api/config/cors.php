<?php
require_once __DIR__ . '/env.php';
// public/api/config/cors.php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = array_filter(array_map('trim', explode(',', (string)Env::get(
    'ACCOUNT_ALLOWED_ORIGINS',
    'https://nex-design.online,http://tauri.localhost,tauri://localhost,http://localhost:1420,http://127.0.0.1:1420,http://localhost:5173'
))));

$isAllowed = false;
if ($origin !== '') {
    if (in_array($origin, $allowedOrigins, true)) {
        $isAllowed = true;
    } elseif (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {
        $isAllowed = true;
    } elseif (str_starts_with($origin, 'tauri://') || str_starts_with($origin, 'http://tauri.')) {
        $isAllowed = true;
    }
}

if ($isAllowed) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-NexDesign-Device");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

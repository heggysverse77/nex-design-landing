<?php
// public/api/auth/me.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

$user = Auth::getCurrentUser();

if (!$user) {
    Response::json([
        'success' => false,
        'authenticated' => false,
        'user' => null
    ], 200);
}

// Get total waitlist count to show position context
$db = Database::getConnection();
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stmt->execute();
$totalUsers = (int)$stmt->fetchColumn();

Response::success([
    'authenticated' => true,
    'user' => $user,
    'total_registered' => $totalUsers
]);

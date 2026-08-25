<?php
// public/api/auth/login.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

$body = Response::getBody();
$email = strtolower(trim($body['email'] ?? ''));
$password = $body['password'] ?? '';

if (empty($email) || empty($password)) {
    Response::error('Email and password are required.');
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    Response::error('Invalid email address or password.', 401);
}

// Update last login
$upStmt = $db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
$upStmt->execute([':id' => $user['id']]);

unset($user['password_hash']);
Auth::loginUser($user);
$token = Auth::generateToken($user);

Response::success([
    'user' => $user,
    'token' => $token
], 'Logged in successfully.');

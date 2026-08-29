<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/offline_lease.php';

$body = Response::getBody();
$email = strtolower(trim($body['email'] ?? '')); $password = (string)($body['password'] ?? '');
$deviceId = trim((string)($body['deviceId'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || $deviceId === '') Response::error('Email, password, and deviceId are required.', 400);
$db = Database::getConnection();
$stmt = $db->prepare('SELECT u.*, COALESCE(p.slug, "starter") AS plan_slug, COALESCE(p.name, "Starter") AS plan_name FROM users u LEFT JOIN plans p ON u.plan_id = p.id WHERE u.email = :email');
$stmt->execute([':email' => $email]); $user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) Response::error('Invalid email address or password.', 401);
if (!OfflineLease::allowsDesktop((string)$user['status'])) Response::error('This account is not currently approved for desktop access.', 403, ['status' => $user['status']]);
$db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $user['id']]);
$lease = OfflineLease::issue($user, $deviceId); $token = Auth::generateToken($user);
Response::success([
    'accessToken' => $token, 'accessTokenExpiresAt' => (time() + 86400 * 30) * 1000,
    'user' => ['accountId' => (string)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'status' => OfflineLease::normalizeStatus((string)$user['status'])],
    'plan' => ['id' => $user['plan_slug'], 'name' => $user['plan_name']], 'offlineLease' => $lease
], 'Desktop login successful.');

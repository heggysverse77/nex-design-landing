<?php
// public/api/admin/update_status.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/mailer.php';

Auth::requireAdmin();
$body = Response::getBody();

$userId = (int)($body['user_id'] ?? 0);
$newStatus = trim($body['status'] ?? '');

$allowedStatuses = ['pending', 'approved', 'invited_to_beta', 'active', 'suspended'];
if (!$userId || !in_array($newStatus, $allowedStatuses)) {
    Response::error('Invalid user ID or status.');
}

$db = Database::getConnection();
$stmt = $db->prepare("UPDATE users SET `status` = :status WHERE `id` = :id AND `role` != 'admin'");
$stmt->execute([':status' => $newStatus, ':id' => $userId]);

if ($newStatus === 'invited_to_beta') {
    $uStmt = $db->prepare("SELECT * FROM users WHERE `id` = :id");
    $uStmt->execute([':id' => $userId]);
    $user = $uStmt->fetch();
    if ($user) {
        try {
            Mailer::sendBetaInvite($user);
        } catch (Exception $e) {
            error_log("Beta invite mail failed: " . $e->getMessage());
        }
    }
}

Response::success(['user_id' => $userId, 'status' => $newStatus], 'User status updated successfully.');

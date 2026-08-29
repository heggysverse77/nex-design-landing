<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/offline_lease.php';
$user = Auth::requireAuth();
$deviceId = trim((string)($_SERVER['HTTP_X_NEXDESIGN_DEVICE'] ?? ''));
if ($deviceId === '') Response::error('X-NexDesign-Device is required.', 400);
$lease = OfflineLease::issue($user, $deviceId);
Response::success(['accountId' => (string)$user['id'], 'email' => $user['email'], 'status' => OfflineLease::normalizeStatus((string)$user['status']), 'statusVersion' => (int)($user['status_version'] ?? 1), 'offlineLease' => $lease]);

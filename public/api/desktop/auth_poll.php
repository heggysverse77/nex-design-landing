<?php
// public/api/desktop/auth_poll.php
require_once __DIR__ . '/../config/cors.php';
header('Content-Type: application/json; charset=UTF-8');

$deviceId = $_GET['deviceId'] ?? '';
if (empty($deviceId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'deviceId query parameter is required'
    ]);
    exit;
}

$tokensDir = __DIR__ . '/../data/auth_sessions';
$deviceHash = hash('sha256', $deviceId);
$sessionFile = $tokensDir . '/' . $deviceHash . '.json';

if (file_exists($sessionFile)) {
    $raw = @file_get_contents($sessionFile);
    if (!empty($raw)) {
        $data = json_decode($raw, true);
        $createdAt = $data['created_at'] ?? 0;
        $token = $data['token'] ?? '';
        
        // Valid for up to 5 minutes
        if (!empty($token) && (time() - $createdAt) <= 300) {
            // Delete file after consumption for security
            @unlink($sessionFile);
            echo json_encode([
                'success' => true,
                'token' => $token
            ]);
            exit;
        }
    }
    // Stale or invalid file
    @unlink($sessionFile);
}

// Still waiting for user to finish Google login in browser
echo json_encode([
    'success' => false,
    'pending' => true
]);

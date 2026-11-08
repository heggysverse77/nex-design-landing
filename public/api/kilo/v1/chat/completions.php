<?php
// public/api/kilo/v1/chat/completions.php
require_once __DIR__ . '/../../config/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => ['message' => 'Method not allowed']]);
    exit;
}

$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Request body empty']]);
    exit;
}

// Forward to Kilo Gateway
$kiloUrl = 'https://ai-gateway.kilo.ai/api/v1/chat/completions';
$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

// Forward Authorization if provided
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!empty($authHeader) && !str_contains($authHeader, 'server-managed')) {
    $headers[] = 'Authorization: ' . $authHeader;
} else {
    $headers[] = 'Authorization: Bearer anonymous';
}

$ch = curl_init($kiloUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $rawInput);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

http_response_code($httpCode ?: 500);
header('Content-Type: ' . ($contentType ?: 'application/json'));
echo $response ?: json_encode(['error' => ['message' => 'Failed to reach Kilo Gateway']]);

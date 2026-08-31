<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/response.php';

$privateKeyPath = trim((string)Env::get('OFFLINE_LEASE_PRIVATE_KEY_PATH', ''));
$keyContent = '';

if ($privateKeyPath !== '' && file_exists($privateKeyPath) && is_readable($privateKeyPath)) {
    $keyContent = file_get_contents($privateKeyPath);
} else {
    // If path specified but doesn't exist yet, attempt to create directory and generate key
    if ($privateKeyPath !== '') {
        $dir = dirname($privateKeyPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $res = openssl_pkey_new([
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => "prime256v1"
        ]);
        if ($res) {
            openssl_pkey_export($res, $keyContent);
            @file_put_contents($privateKeyPath, $keyContent);
            @chmod($privateKeyPath, 0600);
        }
    }
    
    // Fallback to local keys dir if still empty
    if (empty($keyContent)) {
        $keysDir = __DIR__ . '/../data/keys';
        $keyFile = $keysDir . '/lease_private.pem';
        if (file_exists($keyFile) && is_readable($keyFile)) {
            $keyContent = file_get_contents($keyFile);
        } else {
            if (!is_dir($keysDir)) {
                @mkdir($keysDir, 0755, true);
            }
            $res = openssl_pkey_new([
                "private_key_type" => OPENSSL_KEYTYPE_EC,
                "curve_name" => "prime256v1"
            ]);
            if ($res) {
                openssl_pkey_export($res, $keyContent);
                @file_put_contents($keyFile, $keyContent);
            }
        }
    }
}

if (empty($keyContent)) {
    Response::error('Offline access verification is not configured.', 503);
}

$privateKey = openssl_pkey_get_private($keyContent);
if ($privateKey === false) {
    Response::error('Offline access verification is unavailable.', 503);
}

$details = openssl_pkey_get_details($privateKey);
if (!is_array($details) || empty($details['key'])) {
    Response::error('Offline access verification is unavailable.', 503);
}

header('Cache-Control: public, max-age=3600');
Response::success(['publicKeyPem' => $details['key']]);

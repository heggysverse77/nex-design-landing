<?php
// public/api/controllers/DesktopLicenseController.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

class DesktopLicenseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Action Function: Verify Rust Desktop App License Key, Expiration Date, and Account Status
     * @param array $requestBody
     * @param array|null $userFromToken
     * @return void
     */
    public function verifyLicenseAction(array $requestBody, ?array $userFromToken = null): void
    {
        $licenseKey = trim($requestBody['license_key'] ?? '');
        $email = strtolower(trim($requestBody['email'] ?? ''));

        if (empty($licenseKey) && empty($email) && !$userFromToken) {
            Response::json([
                'success' => false,
                'valid' => false,
                'allow_app_access' => false,
                'error_code' => 'MISSING_CREDENTIALS',
                'reason' => 'Please provide a valid license key, account email, or authorization token.'
            ], 400);
        }

        $sql = "SELECT 
                    u.id, u.name, u.email, u.status, u.restriction_reason, u.license_key, u.preferred_os, u.waitlist_number, u.plan_expires_at,
                    p.id AS plan_id, p.slug AS plan_slug, p.name AS plan_name, p.price_monthly,
                    p.max_devices, p.max_resolution, p.ai_features, p.cloud_rendering, p.team_collaboration
                FROM users u
                INNER JOIN plans p ON u.plan_id = p.id
                WHERE 1=1 ";

        $params = [];

        if ($userFromToken) {
            $sql .= "AND u.id = :uid ";
            $params[':uid'] = (int)$userFromToken['id'];
        } elseif (!empty($licenseKey)) {
            $sql .= "AND u.license_key = :lk ";
            $params[':lk'] = $licenseKey;
        } else {
            $sql .= "AND u.email = :email ";
            $params[':email'] = $email;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $record = $stmt->fetch();

        if (!$record) {
            Response::json([
                'success' => false,
                'valid' => false,
                'allow_app_access' => false,
                'error_code' => 'INVALID_LICENSE',
                'reason' => 'No active user account found matching the provided license key.'
            ], 404);
        }

        // 1. Action Check: Check if Account Status is Restricted or Suspended
        if (in_array(strtolower($record['status']), ['restricted', 'suspended', 'blocked'])) {
            $reasonNote = !empty($record['restriction_reason']) 
                ? $record['restriction_reason'] 
                : 'Your account access has been suspended by the administrator.';

            Response::json([
                'success' => false,
                'valid' => false,
                'allow_app_access' => false,
                'error_code' => 'ACCOUNT_RESTRICTED',
                'status' => $record['status'],
                'reason' => $reasonNote
            ], 403);
        }

        // 2. Action Check: Subscription Expiration Check
        if (!empty($record['plan_expires_at'])) {
            $expiryTimestamp = strtotime($record['plan_expires_at']);
            if ($expiryTimestamp > 0 && $expiryTimestamp < time()) {
                $expiryFormatted = date('Y-m-d', $expiryTimestamp);
                Response::json([
                    'success' => false,
                    'valid' => false,
                    'allow_app_access' => false,
                    'error_code' => 'SUBSCRIPTION_EXPIRED',
                    'status' => 'expired',
                    'plan_expires_at' => $record['plan_expires_at'],
                    'reason' => "Your package subscription expired on {$expiryFormatted}. Please renew your plan."
                ], 403);
            }
        }

        // 3. Action Update: Record Last Login Timestamp
        try {
            $updateStmt = $this->db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
            $updateStmt->execute([':id' => $record['id']]);
        } catch (Exception $e) {}

        // 4. Action Return: Valid Access & Package Capabilities
        Response::json([
            'success' => true,
            'valid' => true,
            'allow_app_access' => true,
            'status' => $record['status'],
            'user' => [
                'id' => (int)$record['id'],
                'name' => $record['name'],
                'email' => $record['email'],
                'preferred_os' => $record['preferred_os'],
                'waitlist_number' => (int)$record['waitlist_number']
            ],
            'package_plan' => [
                'id' => (int)$record['plan_id'],
                'slug' => $record['plan_slug'],
                'name' => $record['plan_name'],
                'price_monthly' => (float)$record['price_monthly'],
                'expires_at' => $record['plan_expires_at']
            ],
            'license_key' => $record['license_key'],
            'capabilities' => [
                'max_devices' => (int)$record['max_devices'],
                'max_resolution' => $record['max_resolution'],
                'ai_features' => (bool)$record['ai_features'],
                'cloud_rendering' => (bool)$record['cloud_rendering'],
                'team_collaboration' => (bool)$record['team_collaboration']
            ]
        ]);
    }
}

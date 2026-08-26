<?php
// public/api/controllers/UserController.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

class UserController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Action Function 1: Change User Package Plan
     * @param int $userId
     * @param string $planSlug ('starter', 'professional', 'teams')
     * @return array
     */
    public function changePlan(int $userId, string $planSlug): array
    {
        $planSlug = strtolower(trim($planSlug));
        
        $stmt = $this->db->prepare("SELECT id, name, slug FROM plans WHERE slug = :slug");
        $stmt->execute([':slug' => $planSlug]);
        $plan = $stmt->fetch();

        if (!$plan) {
            $stmt->execute([':slug' => 'starter']);
            $plan = $stmt->fetch();
        }

        $planId = (int)$plan['id'];

        $update = $this->db->prepare("UPDATE users SET `plan_id` = :plan_id WHERE `id` = :id");
        $update->execute([':plan_id' => $planId, ':id' => $userId]);

        return [
            'plan_id' => $planId,
            'plan_slug' => $plan['slug'],
            'plan_name' => $plan['name']
        ];
    }

    /**
     * Action Function 2: Restrict or Suspend User Account Access
     * @param int $userId
     * @param string $status ('restricted', 'suspended', 'active')
     * @param string|null $reason Note displayed to user in Rust Desktop App
     * @return array
     */
    public function restrictUser(int $userId, string $status, ?string $reason = null): array
    {
        $status = strtolower(trim($status));
        $reasonNote = trim($reason ?? '');

        if (in_array($status, ['active', 'invited_to_beta', 'approved'])) {
            $reasonNote = null;
        }

        $update = $this->db->prepare("UPDATE users SET `status` = :status, `restriction_reason` = :reason WHERE `id` = :id");
        $update->execute([
            ':status' => $status,
            ':reason' => $reasonNote ?: null,
            ':id' => $userId
        ]);

        return [
            'status' => $status,
            'restriction_reason' => $reasonNote
        ];
    }

    /**
     * Action Function 3: Generate Unique Desktop License Key
     * @param int $userId
     * @param string $planSlug
     * @return string
     */
    public function generateLicenseKey(int $userId, string $planSlug): string
    {
        $prefix = ($planSlug === 'teams') ? 'NEX-TEAM' : (($planSlug === 'professional') ? 'NEX-PRO' : 'NEX-STR');
        $bytes = strtoupper(bin2hex(random_bytes(6)));
        $licenseKey = $prefix . '-' . substr($bytes, 0, 4) . '-' . substr($bytes, 4, 4) . '-' . substr($bytes, 8, 4);

        $update = $this->db->prepare("UPDATE users SET `license_key` = :key WHERE `id` = :id");
        $update->execute([':key' => $licenseKey, ':id' => $userId]);

        return $licenseKey;
    }

    /**
     * Feature 1: Revoke Old Key & Regenerate Fresh License Key (If leaked/stolen)
     * @param int $userId
     * @return string New License Key
     */
    public function revokeAndRegenerateLicenseKey(int $userId): string
    {
        $stmt = $this->db->prepare("SELECT u.id, p.slug FROM users u INNER JOIN plans p ON u.plan_id = p.id WHERE u.id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('User not found for key regeneration.', 404);
        }

        return $this->generateLicenseKey($userId, $user['slug']);
    }

    /**
     * Feature 2: Set Subscription Expiry Date (30_days, 1_year, custom, lifetime)
     * @param int $userId
     * @param string|null $type ('30_days', '1_year', 'custom', 'lifetime')
     * @param string|null $customDate Format: 'YYYY-MM-DD HH:MM:SS'
     * @return string|null Resulting expiration timestamp or null
     */
    public function setPlanExpiration(int $userId, ?string $type, ?string $customDate = null): ?string
    {
        $expiresAt = null;

        if ($type === '30_days') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        } elseif ($type === '1_year') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
        } elseif ($type === 'custom' && !empty($customDate)) {
            $expiresAt = date('Y-m-d H:i:s', strtotime($customDate));
        } elseif ($type === 'lifetime' || $type === 'none') {
            $expiresAt = null;
        }

        $stmt = $this->db->prepare("UPDATE users SET `plan_expires_at` = :exp WHERE `id` = :id");
        $stmt->execute([':exp' => $expiresAt, ':id' => $userId]);

        return $expiresAt;
    }

    /**
     * Feature 3: Bulk Actions on Selected Array of Users
     * @param array $userIds List of User IDs
     * @param array $updates Action parameters (plan_slug, status, restriction_reason, regenerate_keys, expiry_type)
     * @return array Summary of updated user records
     */
    public function bulkUpdateUsers(array $userIds, array $updates): array
    {
        $updatedCount = 0;
        $results = [];

        foreach ($userIds as $id) {
            $userId = (int)$id;
            if ($userId <= 0) continue;

            if (!empty($updates['plan_slug'])) {
                $this->changePlan($userId, $updates['plan_slug']);
            }

            if (!empty($updates['status'])) {
                $reason = $updates['restriction_reason'] ?? null;
                $this->restrictUser($userId, $updates['status'], $reason);
            }

            if (!empty($updates['expiry_type'])) {
                $customDate = $updates['custom_expiry_date'] ?? null;
                $this->setPlanExpiration($userId, $updates['expiry_type'], $customDate);
            }

            if (!empty($updates['regenerate_keys']) && $updates['regenerate_keys'] === true) {
                $this->revokeAndRegenerateLicenseKey($userId);
            }

            $updatedCount++;
            $results[] = $userId;
        }

        return [
            'updated_count' => $updatedCount,
            'user_ids' => $results
        ];
    }

    /**
     * Main Controller Action Entry Point
     * @param array $requestBody
     * @return void
     */
    public function updateUserAction(array $requestBody): void
    {
        // Handle Bulk Actions request if user_ids array is passed
        if (!empty($requestBody['user_ids']) && is_array($requestBody['user_ids'])) {
            $bulkResult = $this->bulkUpdateUsers($requestBody['user_ids'], $requestBody);
            Response::success($bulkResult, "Bulk update completed for {$bulkResult['updated_count']} users.");
            return;
        }

        $userId = (int)($requestBody['user_id'] ?? 0);
        $planSlug = strtolower(trim($requestBody['plan_slug'] ?? 'starter'));
        $status = strtolower(trim($requestBody['status'] ?? 'active'));
        $restrictionReason = trim($requestBody['restriction_reason'] ?? '');

        if ($userId <= 0) {
            Response::error('Invalid user ID provided.');
        }

        // Fetch current user
        $userStmt = $this->db->prepare("SELECT id, name, email, license_key, plan_expires_at FROM users WHERE id = :id");
        $userStmt->execute([':id' => $userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            Response::error('User account not found.', 404);
        }

        // Execute action functions
        $planInfo = $this->changePlan($userId, $planSlug);
        $restrictionInfo = $this->restrictUser($userId, $status, $restrictionReason);

        // Check key regeneration request
        $licenseKey = $user['license_key'];
        if (!empty($requestBody['regenerate_key']) || empty($licenseKey)) {
            $licenseKey = $this->revokeAndRegenerateLicenseKey($userId);
        }

        // Check expiration update request
        $expiresAt = $user['plan_expires_at'];
        if (!empty($requestBody['expiry_type'])) {
            $expiresAt = $this->setPlanExpiration($userId, $requestBody['expiry_type'], $requestBody['custom_expiry_date'] ?? null);
        }

        Response::success([
            'user_id' => $userId,
            'plan_id' => $planInfo['plan_id'],
            'plan_slug' => $planInfo['plan_slug'],
            'plan_name' => $planInfo['plan_name'],
            'status' => $restrictionInfo['status'],
            'restriction_reason' => $restrictionInfo['restriction_reason'],
            'license_key' => $licenseKey,
            'plan_expires_at' => $expiresAt
        ], 'User package plan, expiration, and status updated successfully.');
    }
}

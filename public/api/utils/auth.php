<?php
// public/api/utils/auth.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/response.php';

class Auth {
    public static function loginUser(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$userId && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1] ?? ''))), true);
            if (!empty($decoded['id'])) {
                $userId = $decoded['id'];
            }
        }

        if (!$userId) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT `id`, `name`, `email`, `role`, `user_type`, `institution`, `faculty_major`, `graduation_year`, `student_id_number`, `current_role`, `portfolio_url`, `preferred_os`, `primary_use_case`, `status`, `waitlist_number`, `created_at`, `last_login_at` FROM users WHERE `id` = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function requireAuth(): array {
        $user = self::getCurrentUser();
        if (!$user) {
            Response::error('Authentication required', 401);
        }
        return $user;
    }

    public static function requireAdmin(): array {
        $user = self::requireAuth();
        if ($user['role'] !== 'admin') {
            Response::error('Forbidden: Administrator access required', 403);
        }
        return $user;
    }

    public static function generateToken(array $user): string {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name'],
            'iat' => time(),
            'exp' => time() + (86400 * 30)
        ]));
        $secret = 'nex-design-secret-auth-key-2026';
        $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $sigBase64 = base64_encode($signature);
        return "$header.$payload.$sigBase64";
    }
}

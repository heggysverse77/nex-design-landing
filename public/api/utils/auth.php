<?php
// public/api/utils/auth.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/env.php';
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
            $decoded = self::verifyToken($matches[1]);
            if (!empty($decoded['id'])) $userId = (int)$decoded['id'];
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
        $secret = self::jwtSecret();
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = self::base64UrlEncode(json_encode([
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name'],
            'iat' => time(),
            'exp' => time() + (86400 * 30)
        ]));
        $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $sigBase64 = self::base64UrlEncode($signature);
        return "$header.$payload.$sigBase64";
    }

    public static function verifyToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$headerPart, $payloadPart, $signaturePart] = $parts;
        $header = json_decode(self::base64UrlDecode($headerPart), true);
        $payload = json_decode(self::base64UrlDecode($payloadPart), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256' || !is_array($payload)) return null;
        $expected = hash_hmac('sha256', "$headerPart.$payloadPart", self::jwtSecret(), true);
        $provided = self::base64UrlDecode($signaturePart);
        if (!hash_equals($expected, $provided)) return null;
        if (!isset($payload['exp']) || (int)$payload['exp'] <= time()) return null;
        return $payload;
    }

    private static function jwtSecret(): string {
        $secret = trim((string)Env::get('JWT_SECRET', ''));
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must contain at least 32 characters');
        }
        return $secret;
    }

    private static function base64UrlEncode(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string {
        $padding = strlen($value) % 4;
        if ($padding > 0) $value .= str_repeat('=', 4 - $padding);
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }

    public static function getUserFromToken(): ?array {
        return self::getCurrentUser();
    }
}

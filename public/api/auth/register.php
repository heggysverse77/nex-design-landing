<?php
// public/api/auth/register.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/mailer.php';

$body = Response::getBody();

$name = trim($body['name'] ?? '');
$email = strtolower(trim($body['email'] ?? ''));
$password = $body['password'] ?? '';
$userType = trim($body['user_type'] ?? 'student');
$institution = trim($body['institution'] ?? '');
$facultyMajor = trim($body['faculty_major'] ?? '');
$graduationYear = !empty($body['graduation_year']) ? (int)$body['graduation_year'] : null;
$studentId = trim($body['student_id_number'] ?? '');
$currentRole = trim($body['current_role'] ?? '');
$portfolioUrl = trim($body['portfolio_url'] ?? '');
$preferredOs = trim($body['preferred_os'] ?? 'windows');
$primaryUseCase = trim($body['primary_use_case'] ?? '');
$referralSource = trim($body['referral_source'] ?? '');

// Validation
if (empty($name) || strlen($name) < 2) {
    Response::error('Please enter a valid full name (at least 2 characters).');
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Please enter a valid email address.');
}

if (empty($password) || strlen($password) < 6) {
    Response::error('Password must be at least 6 characters long.');
}

if (empty($institution)) {
    $fieldLabel = ($userType === 'student') ? 'University / College name' : 'Alma Mater / Company name';
    Response::error("Please specify your {$fieldLabel}.");
}

if (empty($facultyMajor)) {
    $fieldLabel = ($userType === 'student') ? 'Faculty / Major of Study' : 'Major or Specialization';
    Response::error("Please specify your {$fieldLabel}.");
}

$db = Database::getConnection();

// Check email uniqueness
$checkStmt = $db->prepare("SELECT `id` FROM users WHERE `email` = :email");
$checkStmt->execute([':email' => $email]);
if ($checkStmt->fetch()) {
    Response::error('This email is already registered. Please sign in or use another email.');
}

// Calculate next waitlist number (MAX + 1 among non-admin users)
$maxStmt = $db->prepare("SELECT MAX(waitlist_number) FROM users WHERE `role` != 'admin' OR `role` IS NULL");
$maxStmt->execute();
$maxWaitlist = (int)$maxStmt->fetchColumn();
$waitlistNumber = ($maxWaitlist > 0) ? $maxWaitlist + 1 : 1;

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$now = date('Y-m-d H:i:s');

$insertStmt = $db->prepare("INSERT INTO users (
    `name`, `email`, `password_hash`, `role`, `user_type`, `institution`, `faculty_major`,
    `graduation_year`, `student_id_number`, `current_role`, `portfolio_url`,
    `preferred_os`, `primary_use_case`, `referral_source`, `status`, `waitlist_number`, `created_at`
) VALUES (
    :name, :email, :password_hash, 'user', :user_type, :institution, :faculty_major,
    :graduation_year, :student_id_number, :current_role, :portfolio_url,
    :preferred_os, :primary_use_case, :referral_source, 'pending', :waitlist_number, :created_at
)");

try {
    $insertStmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':user_type' => $userType,
        ':institution' => $institution,
        ':faculty_major' => $facultyMajor,
        ':graduation_year' => $graduationYear,
        ':student_id_number' => $studentId ?: null,
        ':current_role' => $currentRole ?: null,
        ':portfolio_url' => $portfolioUrl ?: null,
        ':preferred_os' => $preferredOs,
        ':primary_use_case' => $primaryUseCase ?: null,
        ':referral_source' => $referralSource ?: null,
        ':waitlist_number' => $waitlistNumber,
        ':created_at' => $now
    ]);

    $newId = (int)$db->lastInsertId();

    $userPayload = [
        'id' => $newId,
        'name' => $name,
        'email' => $email,
        'role' => 'user',
        'user_type' => $userType,
        'institution' => $institution,
        'faculty_major' => $facultyMajor,
        'graduation_year' => $graduationYear,
        'preferred_os' => $preferredOs,
        'status' => 'pending',
        'waitlist_number' => $waitlistNumber
    ];

    Auth::loginUser($userPayload);
    $token = Auth::generateToken($userPayload);

    // Send styled Welcome & Queue confirmation email
    try {
        Mailer::sendWelcomeEarlyAccess($userPayload);
    } catch (Exception $mailEx) {
        error_log("Failed to send welcome email: " . $mailEx->getMessage());
    }

    Response::success([
        'user' => $userPayload,
        'token' => $token
    ], 'Registration successful! You have reserved your early access spot and confirmation was sent to your email.', 201);
} catch (Exception $e) {
    Response::error('Failed to register: ' . $e->getMessage(), 500);
}

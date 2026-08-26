<?php
// public/api/admin/users.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

Auth::requireAdmin();
$db = Database::getConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$userType = trim($_GET['user_type'] ?? '');
$preferredOs = trim($_GET['preferred_os'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ["role != 'admin'"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE :s OR email LIKE :s OR institution LIKE :s OR faculty_major LIKE :s)";
    $params[':s'] = "%{$search}%";
}

if (!empty($userType)) {
    $where[] = "user_type = :ut";
    $params[':ut'] = $userType;
}

if (!empty($preferredOs)) {
    $where[] = "preferred_os = :pos";
    $params[':pos'] = $preferredOs;
}

if (!empty($status)) {
    $where[] = "status = :st";
    $params[':st'] = $status;
}

$whereClause = implode(' AND ', $where);

// Total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE {$whereClause}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// Fetch records with plans join
$query = "SELECT 
            u.id, u.name, u.email, u.role, u.user_type, u.institution, u.faculty_major, 
            u.graduation_year, u.student_id_number, u.current_role, u.portfolio_url, 
            u.preferred_os, u.primary_use_case, u.status, u.waitlist_number, u.created_at, u.last_login_at,
            u.plan_id, COALESCE(p.slug, 'starter') AS plan_slug, COALESCE(p.name, 'Starter Package') AS plan_name,
            u.plan_expires_at, u.restriction_reason, u.license_key
          FROM users u
          LEFT JOIN plans p ON u.plan_id = p.id
          WHERE {$whereClause} 
          ORDER BY u.id DESC 
          LIMIT {$limit} OFFSET {$offset}";

$dataStmt = $db->prepare($query);
$dataStmt->execute($params);
$users = $dataStmt->fetchAll();

Response::success([
    'users' => $users,
    'pagination' => [
        'current_page' => $page,
        'limit' => $limit,
        'total_records' => $totalRecords,
        'total_pages' => ceil($totalRecords / $limit)
    ]
]);

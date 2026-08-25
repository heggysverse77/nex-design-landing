<?php
// public/api/admin/stats.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

Auth::requireAdmin();
$db = Database::getConnection();

// Total counts
$total = (int)$db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$students = (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND role != 'admin'")->fetchColumn();
$graduates = (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type = 'graduate' AND role != 'admin'")->fetchColumn();
$professionals = (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type = 'professional' AND role != 'admin'")->fetchColumn();

// OS breakdown
$osStmt = $db->query("SELECT preferred_os, COUNT(*) as count FROM users WHERE role != 'admin' GROUP BY preferred_os ORDER BY count DESC");
$osBreakdown = $osStmt->fetchAll();

// Top 5 Institutions / Universities
$instStmt = $db->query("SELECT institution, COUNT(*) as count FROM users WHERE role != 'admin' AND institution != '' GROUP BY institution ORDER BY count DESC LIMIT 6");
$topInstitutions = $instStmt->fetchAll();

// Top 5 Majors
$majorStmt = $db->query("SELECT faculty_major, COUNT(*) as count FROM users WHERE role != 'admin' AND faculty_major != '' GROUP BY faculty_major ORDER BY count DESC LIMIT 6");
$topMajors = $majorStmt->fetchAll();

// Status breakdown
$statusStmt = $db->query("SELECT status, COUNT(*) as count FROM users WHERE role != 'admin' GROUP BY status");
$statusBreakdown = $statusStmt->fetchAll();

Response::success([
    'total_users' => $total,
    'students_count' => $students,
    'graduates_count' => $graduates,
    'professionals_count' => $professionals,
    'os_breakdown' => $osBreakdown,
    'top_institutions' => $topInstitutions,
    'top_majors' => $topMajors,
    'status_breakdown' => $statusBreakdown
]);

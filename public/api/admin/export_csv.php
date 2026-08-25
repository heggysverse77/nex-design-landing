<?php
// public/api/admin/export_csv.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/auth.php';

$admin = Auth::getCurrentUser();
if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    echo "Forbidden: Admin access required.";
    exit;
}

$db = Database::getConnection();
$stmt = $db->query("SELECT id, waitlist_number, name, email, user_type, institution, faculty_major, graduation_year, student_id_number, current_role, portfolio_url, preferred_os, primary_use_case, referral_source, status, created_at, last_login_at FROM users WHERE role != 'admin' ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=nex_design_users_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');
// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, [
    'ID', 'Waitlist #', 'Name', 'Email', 'Type', 'Institution/University', 'Faculty/Major', 
    'Graduation Year', 'Student ID', 'Current Role', 'Portfolio Link', 'Preferred OS', 
    'Use Case', 'Referral Source', 'Status', 'Registered Date', 'Last Login'
]);

foreach ($users as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;

<?php
// public/api/auth/google_callback.php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/offline_lease.php';

$clientId = (string)Env::get('GOOGLE_CLIENT_ID', '');
$clientSecret = (string)Env::get('GOOGLE_CLIENT_SECRET', '');
$redirectUri = (string)Env::get('GOOGLE_REDIRECT_URI', 'https://nex-design.online/api/auth/google_callback.php');

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? 'desktop';
$error = $_GET['error'] ?? '';

function renderError(string $msg) {
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NexDesign - Authentication Failed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; background:#09090b; color:#fafafa; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px; box-sizing:border-box; }
        .card { max-width:440px; width:100%; background:#121215; border:1px solid #27272a; border-radius:20px; padding:32px; text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }
        h1 { font-size:18px; margin:16px 0 8px; color:#f43f5e; }
        p { font-size:13px; color:#a1a1aa; line-height:1.5; margin:0 0 20px; }
        a { display:inline-block; padding:10px 20px; background:#27272a; color:#fff; text-decoration:none; border-radius:10px; font-size:13px; font-weight:600; }
    </style>
</head>
<body>
    <div class="card">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" style="margin:0 auto"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <h1>Authentication Failed</h1>
        <p>{$msg}</p>
        <a href="https://nex-design.online/login">Return to Login</a>
    </div>
</body>
</html>
HTML;
    exit;
}

if (!empty($error)) {
    renderError("Google returned an error: " . htmlspecialchars($error));
}

if (empty($code)) {
    renderError("No authorization code was provided by Google.");
}

// 1. Exchange code for Google Access Token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postFields = http_build_query([
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    renderError("Failed to communicate with Google authentication servers. Please try again.");
}

$tokenData = json_decode($response, true);
$googleAccessToken = $tokenData['access_token'] ?? '';

if (empty($googleAccessToken)) {
    renderError("Invalid response from Google: access token missing.");
}

// 2. Fetch user profile from Google
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $googleAccessToken,
    'Accept: application/json'
]);
$userResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userResponse, true);
$email = strtolower(trim($googleUser['email'] ?? ''));
$name = trim($googleUser['name'] ?? '');
$avatar = trim($googleUser['picture'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    renderError("Could not retrieve a verified email address from your Google profile.");
}

// 3. Find or Create User in Database
$db = Database::getConnection();
$stmt = $db->prepare('SELECT u.*, COALESCE(p.slug, "starter") AS plan_slug, COALESCE(p.name, "Starter") AS plan_name FROM users u LEFT JOIN plans p ON u.plan_id = p.id WHERE u.email = :email');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    // Automatically register new user
    $randomPassword = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
    $userName = !empty($name) ? $name : explode('@', $email)[0];
    
    $insertStmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, status, user_type, institution, faculty_major, preferred_os, primary_use_case, referral_source, created_at, last_login_at) VALUES (:name, :email, :password_hash, "designer", "active", "student", "Google Account", "Interface & System Design", "windows", "UI/UX & Prototyping", "Google OAuth", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    $insertStmt->execute([
        ':name' => $userName,
        ':email' => $email,
        ':password_hash' => $randomPassword
    ]);
    
    // Fetch newly created user with plan
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
} else {
    // Update last login
    $db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $user['id']]);
}

if (!OfflineLease::allowsDesktop((string)$user['status'])) {
    renderError("Your account status is {$user['status']} and does not have active desktop access.");
}

// 4. Issue Token and Lease
$token = Auth::generateToken($user);

// Parse deviceId from state if provided, format: desktop:<deviceId>
$deviceId = 'desktop-browser-auth';
$isDesktop = str_starts_with($state, 'desktop');

if ($isDesktop && str_contains($state, ':')) {
    $parts = explode(':', $state, 2);
    if (!empty($parts[1])) {
        $deviceId = trim($parts[1]);
    }
}

$lease = OfflineLease::issue($user, $deviceId);

$userPayload = [
    'accountId' => (string)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'avatar' => $avatar,
    'status' => OfflineLease::normalizeStatus((string)$user['status']),
    'user_type' => $user['user_type'] ?? 'student',
    'institution' => $user['institution'] ?? '',
    'faculty_major' => $user['faculty_major'] ?? '',
    'graduation_year' => isset($user['graduation_year']) ? (int)$user['graduation_year'] : 2026,
    'student_id_number' => $user['student_id_number'] ?? '',
    'current_role' => $user['current_role'] ?? '',
    'portfolio_url' => $user['portfolio_url'] ?? '',
    'preferred_os' => $user['preferred_os'] ?? 'windows',
    'waitlist_number' => isset($user['waitlist_number']) ? (int)$user['waitlist_number'] : 14,
    'plan_slug' => $user['plan_slug'] ?? 'starter',
    'plan_name' => $user['plan_name'] ?? 'Starter Package',
    'license_key' => $user['license_key'] ?? null
];

$authEnvelope = [
    'accessToken' => $token,
    'accessTokenExpiresAt' => (time() + 86400 * 30) * 1000,
    'user' => $userPayload,
    'plan' => ['id' => $user['plan_slug'], 'name' => $user['plan_name']],
    'offlineLease' => $lease,
    'deviceId' => $deviceId
];

$bundleJson = json_encode($authEnvelope);
$encodedToken = rtrim(strtr(base64_encode($bundleJson), '+/', '-_'), '=');

// If this is purely a web-based login (state === 'web')
if ($state === 'web') {
    Auth::loginUser($user);
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Connecting...</title>
    <script>
        localStorage.setItem('nexdesign:token', {$bundleJson});
        window.location.href = '/account';
    </script>
</head>
<body style="background:#000;color:#fff;font-family:sans-serif;padding:40px;text-align:center;">
    <p>Authentication successful. Redirecting...</p>
</body>
</html>
HTML;
    exit;
}

// If desktop login, render the authorization confirmation and copy card:
header('Content-Type: text/html; charset=UTF-8');
$displayName = htmlspecialchars($user['name']);
$displayEmail = htmlspecialchars($user['email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NexDesign - Connected</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            background:#09090b;
            color:#fafafa;
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:100vh;
            padding:24px;
        }
        .card {
            max-width:480px;
            width:100%;
            background:#121215;
            border:1px solid #27272a;
            border-radius:24px;
            padding:36px;
            text-align:center;
            box-shadow:0 30px 60px -15px rgba(0,0,0,0.7);
        }
        .icon-circle {
            width:64px;
            height:64px;
            border-radius:50%;
            background:rgba(244,63,94,0.12);
            border:1px solid rgba(244,63,94,0.3);
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 20px;
        }
        h1 { font-size:22px; font-weight:700; margin-bottom:8px; }
        p { font-size:13px; color:#a1a1aa; line-height:1.6; margin-bottom:24px; }
        .user-chip {
            display:inline-flex;
            align-items:center;
            gap:10px;
            background:#1c1c20;
            border:1px solid #2e2e34;
            padding:8px 16px;
            border-radius:999px;
            font-size:12px;
            margin-bottom:24px;
        }
        .btn-launch {
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            width:100%;
            padding:14px;
            background:linear-gradient(135deg, #e11d48, #be123c);
            color:#fff;
            border:none;
            border-radius:14px;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            text-decoration:none;
            transition:all 0.2s ease;
            box-shadow:0 10px 25px -5px rgba(225,29,72,0.4);
            margin-bottom:20px;
        }
        .btn-launch:hover { background:linear-gradient(135deg, #f43f5e, #e11d48); transform:translateY(-1px); }
        .token-box {
            background:#0c0c0e;
            border:1px solid #27272a;
            border-radius:14px;
            padding:12px 14px;
            text-align:left;
        }
        .token-label { font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:#71717a; font-weight:600; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center; }
        .token-value {
            font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
            font-size:11px;
            color:#e4e4e7;
            word-break:break-all;
            background:#18181b;
            padding:10px;
            border-radius:8px;
            max-height:80px;
            overflow-y:auto;
        }
        .copy-btn {
            background:#27272a;
            color:#fff;
            border:none;
            padding:4px 10px;
            border-radius:6px;
            font-size:11px;
            cursor:pointer;
            font-weight:500;
        }
        .copy-btn:hover { background:#3f3f46; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h1>Google Sign-In Successful</h1>
        <p>Your Google account has been verified and your NexDesign desktop license is ready.</p>

        <div class="user-chip">
            <strong style="color:#fff"><?= $displayName ?></strong>
            <span style="color:#71717a">•</span>
            <span style="color:#a1a1aa"><?= $displayEmail ?></span>
        </div>

        <a id="launchBtn" href="nexdesign://auth?token=<?= $encodedToken ?>" class="btn-launch">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Open in NexDesign Desktop
        </a>

        <div class="token-box">
            <div class="token-label">
                <span>Desktop Connection Token</span>
                <button type="button" class="copy-btn" id="copyBtn" onclick="copyToken()">Copy Token</button>
            </div>
            <div class="token-value" id="tokenField"><?= $encodedToken ?></div>
        </div>
    </div>

    <script>
        const token = "<?= $encodedToken ?>";
        function copyToken() {
            navigator.clipboard.writeText(token).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.textContent = 'Copied!';
                btn.style.background = '#059669';
                setTimeout(() => {
                    btn.textContent = 'Copy Token';
                    btn.style.background = '#27272a';
                }, 2000);
            });
        }

        // Try to trigger custom protocol automatically
        setTimeout(() => {
            window.location.href = "nexdesign://auth?token=" + encodeURIComponent(token);
        }, 600);
    </script>
</body>
</html>

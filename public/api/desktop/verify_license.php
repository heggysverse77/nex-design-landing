<?php
// public/api/desktop/verify_license.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../controllers/DesktopLicenseController.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

$controller = new DesktopLicenseController();
$body = Response::getBody();
$userFromToken = Auth::getUserFromToken();

$controller->verifyLicenseAction($body, $userFromToken);

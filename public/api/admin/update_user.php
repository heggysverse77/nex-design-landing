<?php
// public/api/admin/update_user.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

Auth::requireAdmin();

$controller = new UserController();
$body = Response::getBody();

$controller->updateUserAction($body);

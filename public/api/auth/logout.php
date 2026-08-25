<?php
// public/api/auth/logout.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';

Auth::logout();
Response::success(null, 'Logged out successfully.');

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /library-system/auth/login.php');
    exit;
}

function require_roles($allowedRoles) {
    if (!in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Access denied</title>'
            . '<link rel="stylesheet" href="/library-system/assets/css/style.css?v=20261114d">'
            . '</head><body class="access-denied"><main class="card">'
            . '<h1>Access denied</h1><p>Your account cannot open this page.</p>'
            . '<a class="button" href="/library-system/dashboard/index.php">Back to dashboard</a>'
            . '</main></body></html>';
        exit;
    }
}
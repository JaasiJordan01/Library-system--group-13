<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Library System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Library System</title>
    <link rel="stylesheet" href="/library-system/assets/css/style.css?v=20261114">
    <script src="/library-system/assets/js/app.js" defer></script>
</head>
<body>
<header class="app-header">
    <a class="brand" href="/library-system/dashboard/index.php">
        <span class="brand-mark" aria-hidden="true">LS</span>
        <span class="brand-copy">
            <strong>Library System</strong>
            <span>Knowledge for everyone</span>
        </span>
    </a>
</header>
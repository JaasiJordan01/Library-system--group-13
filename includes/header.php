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
    <link rel="stylesheet" href="/library-system/assets/css/style.css?v=20261114c">
    <?php if (!empty($extraStylesheet)): ?>
        <link rel="stylesheet" href="<?= e($extraStylesheet) ?>">
    <?php endif; ?>
    <script src="/library-system/assets/js/app.js?v=20261114c" defer></script>
</head>
<body>
<div class="app-shell">
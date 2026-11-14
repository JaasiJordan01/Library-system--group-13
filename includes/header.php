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
    <link rel="stylesheet" href="/library-system/assets/css/style.css">
    <script src="/library-system/assets/js/app.js" defer></script>
</head>
<body>
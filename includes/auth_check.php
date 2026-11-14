<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /library-system/auth/login.php');
    exit;
}
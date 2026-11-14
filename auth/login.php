<?php
session_start();
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /library-system/dashboard/index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter both your username and password.';
    } else {
        require '../config/database.php';
        $stmt = $pdo->prepare(
            'SELECT user_id, username, password_hash, full_name, role
             FROM users
             WHERE username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header('Location: /library-system/dashboard/index.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Library System</title>
    <link rel="stylesheet" href="/library-system/assets/css/style.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <h1>Library System</h1>
    <p>Sign in to manage the library collection.</p>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <?= csrf_field() ?>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" required
                   value="<?= e($username) ?>">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password"
                   autocomplete="current-password" required>
        </div>
        <button class="button" type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
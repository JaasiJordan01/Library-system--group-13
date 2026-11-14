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
             FROM users WHERE username = ?'
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

        $stmt = $pdo->prepare(
            "SELECT member_id, member_number, password_hash, full_name
             FROM members
             WHERE (member_number = ? OR email = ?)
               AND status = 'active' AND password_hash IS NOT NULL
             LIMIT 1"
        );
        $stmt->execute([$username, $username]);
        $member = $stmt->fetch();

        if ($member && password_verify($password, $member['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = 'member:' . $member['member_id'];
            $_SESSION['member_id'] = (int)$member['member_id'];
            $_SESSION['username'] = $member['member_number'];
            $_SESSION['full_name'] = $member['full_name'];
            $_SESSION['role'] = 'student';
            header('Location: /library-system/dashboard/index.php');
            exit;
        }

        $error = 'Invalid username, member number, email, or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Library System</title>
    <link rel="stylesheet" href="/library-system/assets/css/style.css?v=20261114d">
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-intro" aria-label="About the library">
        <a class="brand" href="/library-system/auth/login.php">
            <span class="brand-mark" aria-hidden="true">LS</span>
            <span class="brand-copy">
                <strong>Library System</strong>
                <span>Read. Discover. Grow.</span>
            </span>
        </a>
        <div class="auth-intro-copy">
            <h2>Every story opens <span>a new world.</span></h2>
            <p><strong>A place for every reader.</strong><br>
                Browse books, stay on top of loans, and keep your library community connected.</p>
        </div>
        <div class="auth-audience" aria-label="Library account types">
            <span>Student members</span>
            <span>Librarians</span>
            <span>Administrators</span>
        </div>
    </section>
    <section class="auth-panel" aria-label="Sign in">
        <div class="auth-card">
            <div class="auth-brand">
                <div>
                    <h1>Welcome back</h1>
                    <p class="auth-subtitle">Sign in to your library account.</p>
                </div>
            </div>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username, member number, or email</label>
                    <input id="username" name="username" autocomplete="username" required
                           value="<?= e($username) ?>">
                    <small>Students sign in with the member number or email provided by the library.</small>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-control">
                        <input id="password" name="password" type="password"
                               autocomplete="current-password" required>
                        <button class="password-toggle" type="button"
                                data-toggle-password="password" aria-label="Show password">Show</button>
                    </div>
                </div>
                <button class="button" type="submit">Sign in <span aria-hidden="true">&rarr;</span></button>
            </form>
            <p class="auth-note">New to the library? <a href="register.php">Create a student account</a>.</p>
            <p class="auth-copyright">&copy; <?= date('Y') ?> Library System</p>
        </div>
    </section>
</main>
<script src="/library-system/assets/js/app.js?v=20261114d" defer></script>
</body>
</html>
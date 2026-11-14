<?php
session_start();
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /library-system/dashboard/index.php');
    exit;
}

$errors = [];
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $passwordConfirmation = is_string($_POST['password_confirmation'] ?? null)
        ? $_POST['password_confirmation']
        : '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    if ($fullName === '' || text_length($fullName) > 100) {
        $errors[] = 'Enter your name using no more than 100 characters.';
    }
    if ($email === '' || text_length($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address using no more than 120 characters.';
    }
    if (text_length($phone) > 20) {
        $errors[] = 'Your phone number must be no more than 20 characters.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Your password must be between 8 and 72 bytes.';
    } elseif (!hash_equals($password, $passwordConfirmation)) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        require '../config/database.php';
        $memberNumber = 'STU-' . strtoupper(bin2hex(random_bytes(5)));
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO members
                    (member_number, full_name, email, password_hash, phone, status)
                 VALUES (?, ?, ?, ?, ?, \'active\')'
            );
            $stmt->execute([
                $memberNumber,
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $phone !== '' ? $phone : null,
            ]);

            $memberId = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = 'member:' . $memberId;
            $_SESSION['member_id'] = $memberId;
            $_SESSION['username'] = $memberNumber;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = 'student';
            set_flash('success', 'Your account is ready. Your member number is ' . $memberNumber . '.');
            header('Location: /library-system/dashboard/index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $stmt = $pdo->prepare('SELECT member_id FROM members WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                if ($stmt->fetchColumn()) {
                    $errors[] = 'An account already uses that email. Sign in or use another email.';
                } else {
                    throw $exception;
                }
            } else {
                throw $exception;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create a student account | Library System</title>
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
            <h2>Make room for <span>your next story.</span></h2>
            <p><strong>Join your library community.</strong><br>
                Create a student member account to browse books, track loans, and reserve titles.</p>
        </div>
        <div class="auth-audience" aria-label="Library account types">
            <span>Student members</span>
            <span>Librarians</span>
            <span>Administrators</span>
        </div>
    </section>
    <section class="auth-panel" aria-label="Create a student account">
        <div class="auth-card">
            <div class="auth-brand">
                <div>
                    <h1>Create your account</h1>
                    <p class="auth-subtitle">Student/member access is activated for this demonstration.</p>
                </div>
            </div>
            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <form method="post" action="register.php">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" autocomplete="name" maxlength="100"
                           required value="<?= e($fullName) ?>">
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="120"
                           required value="<?= e($email) ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone (optional)</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="20"
                           value="<?= e($phone) ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-control">
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               minlength="8" maxlength="72" required>
                        <button class="password-toggle" type="button" data-toggle-password="password"
                                aria-label="Show password">Show</button>
                    </div>
                    <small>Use 8 to 72 bytes.</small>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-control">
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               autocomplete="new-password" minlength="8" maxlength="72" required>
                        <button class="password-toggle" type="button"
                                data-toggle-password="password_confirmation" aria-label="Show password">Show</button>
                    </div>
                </div>
                <button class="button" type="submit">Create account <span aria-hidden="true">&rarr;</span></button>
            </form>
            <p class="auth-note">Already have an account? <a href="login.php">Sign in</a>.</p>
            <p class="auth-copyright">&copy; <?= date('Y') ?> Library System</p>
        </div>
    </section>
</main>
<script src="/library-system/assets/js/app.js?v=20261114d" defer></script>
</body>
</html>

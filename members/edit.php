<?php
require_once '../includes/auth_check.php';
require_roles(['admin', 'librarian']);
require_once '../config/database.php';
require_once '../includes/functions.php';

$memberId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
}
if (!$memberId || $memberId < 1) {
    set_flash('danger', 'Invalid member.');
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM members WHERE member_id = ?');
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) {
    set_flash('danger', 'Member not found.');
    header('Location: index.php');
    exit;
}

$errors = [];
$studentPassword = '';
$disableStudentLogin = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentPassword = $_POST['student_password'] ?? '';
    $disableStudentLogin = isset($_POST['disable_student_login']);
    foreach (['member_number', 'full_name', 'email', 'phone', 'status'] as $field) {
        $member[$field] = trim($_POST[$field] ?? '');
    }
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    foreach (['member_number', 'full_name', 'email'] as $required) {
        if ($member[$required] === '') {
            $errors[] = ucfirst(str_replace('_', ' ', $required)) . ' is required.';
        }
    }
    if (text_length($member['member_number']) > 20 || text_length($member['full_name']) > 100
        || text_length($member['email']) > 120 || text_length($member['phone']) > 20) {
        $errors[] = 'One or more values exceed the allowed length.';
    }
    if ($member['email'] !== '' && !filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!in_array($member['status'], ['active', 'suspended'], true)) {
        $errors[] = 'Choose a valid member status.';
    }
    if ($studentPassword !== '' && (strlen($studentPassword) < 8 || strlen($studentPassword) > 72)) {
        $errors[] = 'A student sign-in password must be between 8 and 72 bytes.';
    }

    if (!$errors) {
        try {
            $passwordHash = $studentPassword !== ''
                ? password_hash($studentPassword, PASSWORD_DEFAULT)
                : ($disableStudentLogin ? null : $member['password_hash']);
            $stmt = $pdo->prepare(
                'UPDATE members
                 SET member_number = ?, full_name = ?, email = ?, phone = ?, status = ?,
                     password_hash = ?
                 WHERE member_id = ?'
            );
            $stmt->execute([
                $member['member_number'],
                $member['full_name'],
                $member['email'],
                $member['phone'] !== '' ? $member['phone'] : null,
                $member['status'],
                $passwordHash,
                $memberId,
            ]);
            set_flash('success', $studentPassword !== ''
                ? 'Member updated and student sign-in password changed.'
                : ($disableStudentLogin ? 'Member updated and student sign-in disabled.' : 'Member updated.'));
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'That member number or email is already in use.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Edit member';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <header class="page-heading"><h1>Edit member</h1></header>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form class="card form-grid" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="member_id" value="<?= e($memberId) ?>">
        <div class="field">
            <label for="member_number">Member number</label>
            <input id="member_number" name="member_number" maxlength="20" required value="<?= e($member['member_number']) ?>">
        </div>
        <div class="field">
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" maxlength="100" required value="<?= e($member['full_name']) ?>">
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="120" required value="<?= e($member['email']) ?>">
        </div>
        <div class="field">
            <label for="phone">Phone (optional)</label>
            <input id="phone" name="phone" maxlength="20" value="<?= e($member['phone']) ?>">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active"<?= $member['status'] === 'active' ? ' selected' : '' ?>>Active</option>
                <option value="suspended"<?= $member['status'] === 'suspended' ? ' selected' : '' ?>>Suspended</option>
            </select>
        </div>
        <div class="field">
            <label for="student_password">Set a new student password (optional)</label>
            <input id="student_password" name="student_password" type="password"
                   autocomplete="new-password" minlength="8" maxlength="72">
            <small>Current access: <?= $member['password_hash'] ? 'enabled' : 'not set' ?>. Leave blank to keep it unchanged.</small>
        </div>
        <div class="field field-full">
            <label class="checkbox-field">
                <input type="checkbox" name="disable_student_login" value="1"
                       <?= $disableStudentLogin ? 'checked' : '' ?>>
                Disable this member's student sign-in
            </label>
        </div>
        <div class="field-full form-actions">
            <button class="button" type="submit">Update member</button>
            <a class="button button-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</main>
<?php require '../includes/footer.php'; ?>

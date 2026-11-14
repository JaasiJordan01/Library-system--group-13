<?php
require_once '../includes/auth_check.php';
require_roles(['admin', 'librarian']);
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Invalid member deletion request.');
}

$memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
if (!$memberId || $memberId < 1) {
    set_flash('danger', 'Invalid member.');
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM members WHERE member_id = ?');
    $stmt->execute([$memberId]);
    set_flash($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount() ? 'Member deleted.' : 'Member not found.');
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        set_flash('danger', 'This member has loan records and cannot be deleted.');
    } else {
        throw $exception;
    }
}

header('Location: index.php');
exit;
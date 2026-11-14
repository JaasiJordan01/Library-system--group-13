<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$errors = [];
$isStudent = ($_SESSION['role'] ?? '') === 'student';
$memberId = $isStudent
    ? (string)($_SESSION['member_id'] ?? '')
    : trim($_POST['member_id'] ?? '');
$bookId = trim($_POST['book_id'] ?? $_GET['book_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isStudent) {
        $memberId = trim($_POST['member_id'] ?? '');
    }
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } elseif ((!$isStudent && !ctype_digit($memberId)) || !ctype_digit($bookId)) {
        $errors[] = $isStudent ? 'Select a book.' : 'Select an active member and a book.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status FROM members WHERE member_id = ? FOR UPDATE');
            $stmt->execute([(int)$memberId]);
            $member = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT available_copies FROM books WHERE book_id = ? FOR UPDATE');
            $stmt->execute([(int)$bookId]);
            $book = $stmt->fetch();

            if (!$member || $member['status'] !== 'active') {
                $errors[] = 'Reservations require an active member.';
            } elseif (!$book) {
                $errors[] = 'The selected book does not exist.';
            } elseif ((int)$book['available_copies'] > 0) {
                $errors[] = 'Copies are available; issue the book instead of reserving it.';
            }

            if (!$errors) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM reservations
                     WHERE member_id = ? AND book_id = ? AND status = 'pending'"
                );
                $stmt->execute([(int)$memberId, (int)$bookId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $errors[] = 'This member already has a pending reservation for this book.';
                }
            }

            if (!$errors) {
                $stmt = $pdo->prepare(
                    "INSERT INTO reservations (member_id, book_id, status)
                     VALUES (?, ?, 'pending')"
                );
                $stmt->execute([(int)$memberId, (int)$bookId]);
                $pdo->commit();
                set_flash('success', 'Reservation created.');
                header('Location: index.php');
                exit;
            }

            $pdo->rollBack();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

$members = $pdo->query(
    "SELECT member_id, member_number, full_name FROM members
     WHERE status = 'active' ORDER BY full_name"
)->fetchAll();
$books = $pdo->query(
    'SELECT book_id, title FROM books WHERE available_copies = 0 ORDER BY title'
)->fetchAll();

$pageTitle = 'New reservation';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <header class="page-heading"><h1>New reservation</h1></header>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?php if ((!$isStudent && !$members) || !$books): ?>
        <p class="alert alert-warning">
            <?= $isStudent
                ? 'There are no currently unavailable books to reserve.'
                : 'Reservations require an active member and a book with no available copies.' ?>
        </p>
    <?php else: ?>
        <form class="card form-grid" method="post">
            <?= csrf_field() ?>
            <?php if ($isStudent): ?>
                <input type="hidden" name="member_id" value="<?= e($memberId) ?>">
                <p class="field-full">Reserving as <strong><?= e($_SESSION['full_name']) ?></strong></p>
            <?php else: ?>
                <div class="field">
                    <label for="member_id">Member</label>
                    <select id="member_id" name="member_id" required>
                        <option value="">Select a member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= e($member['member_id']) ?>"<?= $memberId == $member['member_id'] ? ' selected' : '' ?>>
                                <?= e($member['full_name']) ?> (<?= e($member['member_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="field">
                <label for="book_id">Unavailable book</label>
                <select id="book_id" name="book_id" required>
                    <option value="">Select a book</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= e($book['book_id']) ?>"<?= $bookId == $book['book_id'] ? ' selected' : '' ?>><?= e($book['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-full form-actions">
                <button class="button" type="submit">Reserve</button>
                <a class="button button-secondary" href="index.php">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>
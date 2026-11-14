<?php
require_once '../includes/auth_check.php';
require_roles(['admin', 'librarian']);
require_once '../config/database.php';
require_once '../includes/functions.php';

$errors = [];
$memberId = trim($_POST['member_id'] ?? '');
$bookId = trim($_POST['book_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } elseif (!ctype_digit($memberId) || !ctype_digit($bookId)) {
        $errors[] = 'Select a member and a book.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT status FROM members WHERE member_id = ? FOR UPDATE');
            $stmt->execute([(int)$memberId]);
            $member = $stmt->fetch();
            if (!$member || $member['status'] !== 'active') {
                $errors[] = 'Only active members can borrow books.';
            }

            $stmt = $pdo->prepare('SELECT available_copies FROM books WHERE book_id = ? FOR UPDATE');
            $stmt->execute([(int)$bookId]);
            $book = $stmt->fetch();
            if (!$book || (int)$book['available_copies'] < 1) {
                $errors[] = 'No copies are currently available.';
            }

            if (!$errors) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM loans
                     WHERE member_id = ? AND book_id = ?
                       AND status IN ('borrowed', 'overdue')"
                );
                $stmt->execute([(int)$memberId, (int)$bookId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $errors[] = 'This member already has this book on loan.';
                }
            }

            if (!$errors) {
                $stmt = $pdo->prepare(
                    "INSERT INTO loans (member_id, book_id, date_issued, due_date, status)
                     VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'borrowed')"
                );
                $stmt->execute([(int)$memberId, (int)$bookId]);
                $stmt = $pdo->prepare(
                    'UPDATE books SET available_copies = available_copies - 1
                     WHERE book_id = ? AND available_copies > 0'
                );
                $stmt->execute([(int)$bookId]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Book availability changed during checkout.');
                }
                $pdo->commit();
                set_flash('success', 'Book issued successfully. It is due in 14 days.');
                header('Location: index.php');
                exit;
            }

            $pdo->rollBack();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'The book could not be issued. Please refresh and try again.';
        }
    }
}

$members = $pdo->query(
    "SELECT member_id, member_number, full_name FROM members
     WHERE status = 'active' ORDER BY full_name"
)->fetchAll();
$books = $pdo->query(
    'SELECT book_id, title, available_copies FROM books
     WHERE available_copies > 0 ORDER BY title'
)->fetchAll();

$pageTitle = 'Issue a book';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <header class="page-heading"><h1>Issue a book</h1></header>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?php if (!$members || !$books): ?>
        <p class="alert alert-warning">Issuing a book requires an active member and at least one available book.</p>
    <?php else: ?>
        <form class="card form-grid" method="post">
            <?= csrf_field() ?>
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
            <div class="field">
                <label for="book_id">Book</label>
                <select id="book_id" name="book_id" required>
                    <option value="">Select a book</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= e($book['book_id']) ?>"<?= $bookId == $book['book_id'] ? ' selected' : '' ?>>
                            <?= e($book['title']) ?> (<?= e($book['available_copies']) ?> available)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-full form-actions">
                <button class="button" type="submit">Issue for 14 days</button>
                <a class="button button-secondary" href="index.php">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>

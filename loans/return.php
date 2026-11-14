<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Invalid book return request.');
}

$loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);
if (!$loanId || $loanId < 1) {
    set_flash('danger', 'Invalid loan.');
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "SELECT loan_id, book_id, due_date FROM loans
         WHERE loan_id = ? AND status IN ('borrowed', 'overdue')
         FOR UPDATE"
    );
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();

    if (!$loan) {
        $pdo->rollBack();
        set_flash('danger', 'Loan not found or already returned.');
        header('Location: index.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE loans SET date_returned = CURDATE(), status = 'returned'
         WHERE loan_id = ? AND status IN ('borrowed', 'overdue')"
    );
    $stmt->execute([$loanId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Loan status changed during return.');
    }

    $stmt = $pdo->prepare(
        'UPDATE books SET available_copies = available_copies + 1
         WHERE book_id = ? AND available_copies < total_copies'
    );
    $stmt->execute([(int)$loan['book_id']]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Book copy count could not be updated.');
    }

    $stmt = $pdo->prepare(
        'SELECT GREATEST(DATEDIFF(CURDATE(), ?), 0)'
    );
    $stmt->execute([$loan['due_date']]);
    $daysLate = (int)$stmt->fetchColumn();
    if ($daysLate > 0) {
        $stmt = $pdo->prepare('INSERT INTO fines (loan_id, amount) VALUES (?, ?)');
        $stmt->execute([$loanId, $daysLate * 0.50]);
    }

    $pdo->commit();
    set_flash('success', $daysLate > 0
        ? 'Book returned. A fine of ' . number_format($daysLate * 0.50, 2) . ' was recorded.'
        : 'Book returned successfully.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'The book could not be returned. Please refresh and try again.');
}

header('Location: index.php');
exit;

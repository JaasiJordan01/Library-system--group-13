<?php
require_once '../includes/auth_check.php';
require_roles(['admin', 'librarian']);
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use the reservation issue form.');
}

$reservationId = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$reservationId || $reservationId < 1 || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('danger', 'Invalid or expired reservation request.');
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT book_id FROM reservations
     WHERE reservation_id = ? AND status = 'pending'"
);
$stmt->execute([$reservationId]);
$bookId = $stmt->fetchColumn();
if (!$bookId) {
    set_flash('danger', 'Pending reservation not found.');
    header('Location: index.php');
    exit;
}

$errors = [];
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT available_copies FROM books WHERE book_id = ? FOR UPDATE');
    $stmt->execute([(int)$bookId]);
    $book = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT member_id FROM reservations
         WHERE reservation_id = ? AND book_id = ? AND status = 'pending'
         FOR UPDATE"
    );
    $stmt->execute([$reservationId, (int)$bookId]);
    $reservation = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT reservation_id, member_id FROM reservations
         WHERE book_id = ? AND status = 'pending'
         ORDER BY reserved_at, reservation_id
         LIMIT 1 FOR UPDATE"
    );
    $stmt->execute([(int)$bookId]);
    $firstReservation = $stmt->fetch();

    if (!$book || !$reservation) {
        $errors[] = 'The book or pending reservation no longer exists.';
    } elseif ((int)$book['available_copies'] < 1) {
        $errors[] = 'No copies are currently available.';
    } elseif (!$firstReservation || (int)$firstReservation['reservation_id'] !== (int)$reservationId) {
        $errors[] = 'Fulfill the oldest pending reservation first.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT status FROM members WHERE member_id = ? FOR UPDATE');
        $stmt->execute([(int)$reservation['member_id']]);
        $member = $stmt->fetch();
        if (!$member || $member['status'] !== 'active') {
            $errors[] = 'Only active members can receive a book.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM loans
             WHERE member_id = ? AND book_id = ?
               AND status IN ('borrowed', 'overdue')"
        );
        $stmt->execute([(int)$reservation['member_id'], (int)$bookId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'This member already has the book on loan.';
        }
    }

    if ($errors) {
        $pdo->rollBack();
        set_flash('danger', implode(' ', $errors));
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO loans (member_id, book_id, date_issued, due_date, status)
             VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'borrowed')"
        );
        $stmt->execute([(int)$reservation['member_id'], (int)$bookId]);

        $stmt = $pdo->prepare(
            'UPDATE books SET available_copies = available_copies - 1
             WHERE book_id = ? AND available_copies > 0'
        );
        $stmt->execute([(int)$bookId]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Book availability changed during reservation fulfillment.');
        }

        $stmt = $pdo->prepare(
            "UPDATE reservations SET status = 'fulfilled'
             WHERE reservation_id = ? AND status = 'pending'"
        );
        $stmt->execute([$reservationId]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Reservation status changed during fulfillment.');
        }

        $pdo->commit();
        set_flash('success', 'Book issued to the member. The reservation has been fulfilled; it is due in 14 days.');
    }
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'The reservation could not be fulfilled. Refresh and try again.');
}

header('Location: index.php');
exit;

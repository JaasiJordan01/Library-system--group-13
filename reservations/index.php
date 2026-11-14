<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(405);
        exit('Invalid reservation update request.');
    }

    $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    if (!$reservationId || $reservationId < 1) {
        set_flash('danger', 'Invalid reservation.');
    } else {
        $stmt = $pdo->prepare(
            "UPDATE reservations SET status = 'cancelled'
             WHERE reservation_id = ? AND status = 'pending'"
        );
        $stmt->execute([$reservationId]);
        set_flash($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount()
            ? 'Reservation cancelled.'
            : 'Pending reservation not found.');
    }

    header('Location: index.php');
    exit;
}

$reservations = $pdo->query(
    'SELECT reservations.reservation_id, reservations.reserved_at, reservations.status,
            members.member_number, members.full_name, books.title
     FROM reservations
     INNER JOIN members ON members.member_id = reservations.member_id
     INNER JOIN books ON books.book_id = reservations.book_id
     ORDER BY reservations.reserved_at DESC'
)->fetchAll();

$pageTitle = 'Reservations';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <h1>Reservations</h1>
        <a class="button" href="create.php">New reservation</a>
    </header>
    <?php if (!$reservations): ?>
        <p class="card empty-state">No reservations have been recorded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Book</th><th>Member</th><th>Reserved</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><?= e($reservation['title']) ?></td>
                        <td><?= e($reservation['full_name']) ?> (<?= e($reservation['member_number']) ?>)</td>
                        <td><?= e($reservation['reserved_at']) ?></td>
                        <td><?= e(ucfirst($reservation['status'])) ?></td>
                        <td>
                            <?php if ($reservation['status'] === 'pending'): ?>
                                <form class="inline-form" method="post" data-confirm="Cancel this reservation?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="reservation_id" value="<?= e($reservation['reservation_id']) ?>">
                                    <button class="button button-danger" type="submit">Cancel</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>
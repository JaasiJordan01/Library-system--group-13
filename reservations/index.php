<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$isStudent = ($_SESSION['role'] ?? '') === 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(405);
        exit('Invalid reservation update request.');
    }

    $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    if (!$reservationId || $reservationId < 1) {
        set_flash('danger', 'Invalid reservation.');
    } else {
        $sql = "UPDATE reservations SET status = 'cancelled'
                WHERE reservation_id = ? AND status = 'pending'";
        $params = [$reservationId];
        if ($isStudent) {
            $sql .= ' AND member_id = ?';
            $params[] = (int)$_SESSION['member_id'];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        set_flash($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount()
            ? 'Reservation cancelled.'
            : 'Pending reservation not found.');
    }

    header('Location: index.php');
    exit;
}

$sql = 'SELECT reservations.reservation_id, reservations.reserved_at, reservations.status,
               members.member_number, members.full_name, members.status AS member_status,
               books.title, books.book_id, books.available_copies,
               CASE WHEN NOT EXISTS (
                   SELECT 1 FROM reservations AS earlier
                   WHERE earlier.book_id = reservations.book_id
                     AND earlier.status = \'pending\'
                     AND (earlier.reserved_at < reservations.reserved_at
                          OR (earlier.reserved_at = reservations.reserved_at
                              AND earlier.reservation_id < reservations.reservation_id))
               ) THEN 1 ELSE 0 END AS is_first_in_queue
        FROM reservations
        INNER JOIN members ON members.member_id = reservations.member_id
        INNER JOIN books ON books.book_id = reservations.book_id';
$params = [];
if ($isStudent) {
    $sql .= ' WHERE reservations.member_id = ?';
    $params[] = (int)$_SESSION['member_id'];
}
$sql .= ' ORDER BY reservations.reserved_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$pageTitle = $isStudent ? 'My reservations' : 'Reservations';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <div>
            <h1><?= $isStudent ? 'My reservations' : 'Reservations' ?></h1>
            <p><?= $isStudent
                ? 'Track your requests; staff issue copies in queue order.'
                : 'Issue available copies to the oldest active request first.' ?></p>
        </div>
        <a class="button" href="create.php">New reservation</a>
    </header>
    <?php if (!$reservations): ?>
        <p class="card empty-state">No reservations have been recorded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Book</th><?php if (!$isStudent): ?><th>Member</th><?php endif; ?><th>Reserved</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><?= e($reservation['title']) ?></td>
                        <?php if (!$isStudent): ?>
                            <td><?= e($reservation['full_name']) ?> (<?= e($reservation['member_number']) ?>)</td>
                        <?php endif; ?>
                        <td><?= e($reservation['reserved_at']) ?></td>
                        <td><?= e(ucfirst($reservation['status'])) ?></td>
                        <td>
                            <?php if ($reservation['status'] === 'pending'): ?>
                                <div class="actions">
                                    <?php if (!$isStudent && (int)$reservation['available_copies'] > 0
                                        && (int)$reservation['is_first_in_queue'] === 1
                                        && $reservation['member_status'] === 'active'): ?>
                                        <form class="inline-form" method="post" action="fulfill.php"
                                              data-confirm="Issue this book to the member and fulfill the reservation?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="reservation_id" value="<?= e($reservation['reservation_id']) ?>">
                                            <button class="button button-accent" type="submit">Issue book</button>
                                        </form>
                                    <?php elseif (!$isStudent && (int)$reservation['available_copies'] > 0
                                        && (int)$reservation['is_first_in_queue'] !== 1): ?>
                                        <span>Waiting for earlier request</span>
                                    <?php elseif (!$isStudent && (int)$reservation['available_copies'] === 0): ?>
                                        <span>Waiting for a copy</span>
                                    <?php endif; ?>
                                    <form class="inline-form" method="post" data-confirm="Cancel this reservation?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reservation_id" value="<?= e($reservation['reservation_id']) ?>">
                                        <button class="button button-danger" type="submit">Cancel</button>
                                    </form>
                                </div>
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
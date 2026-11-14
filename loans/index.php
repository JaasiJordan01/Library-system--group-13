<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$loans = $pdo->query(
    "SELECT loans.loan_id, loans.date_issued, loans.due_date, loans.date_returned,
            CASE
                WHEN loans.status = 'borrowed' AND loans.due_date < CURDATE() THEN 'overdue'
                ELSE loans.status
            END AS display_status,
            members.member_number, members.full_name, books.title
     FROM loans
     INNER JOIN members ON members.member_id = loans.member_id
     INNER JOIN books ON books.book_id = loans.book_id
     ORDER BY (loans.status IN ('borrowed', 'overdue')) DESC, loans.due_date"
)->fetchAll();

$pageTitle = 'Loans';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <h1>Loans</h1>
        <a class="button" href="issue.php">Issue a book</a>
    </header>
    <?php if (!$loans): ?>
        <p class="card empty-state">No loans have been recorded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Book</th><th>Member</th><th>Issued</th><th>Due</th><th>Returned</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td><?= e($loan['title']) ?></td>
                        <td><?= e($loan['full_name']) ?> (<?= e($loan['member_number']) ?>)</td>
                        <td><?= e($loan['date_issued']) ?></td>
                        <td><?= e($loan['due_date']) ?></td>
                        <td><?= e($loan['date_returned'] ?: '—') ?></td>
                        <td><?= e(ucfirst($loan['display_status'])) ?></td>
                        <td>
                            <?php if (in_array($loan['display_status'], ['borrowed', 'overdue'], true)): ?>
                                <form class="inline-form" method="post" action="return.php"
                                      data-confirm="Record this book as returned?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="loan_id" value="<?= e($loan['loan_id']) ?>">
                                    <button class="button" type="submit">Return</button>
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
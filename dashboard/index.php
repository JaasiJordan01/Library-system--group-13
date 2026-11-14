<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$role = $_SESSION['role'] ?? 'librarian';
$isStudent = $role === 'student';
$pageTitle = $isStudent
    ? 'My library'
    : ($role === 'admin' ? 'Administrator dashboard' : 'Librarian dashboard');

if ($isStudent) {
    $memberId = (int)($_SESSION['member_id'] ?? 0);
    if ($memberId < 1) {
        http_response_code(403);
        exit('Student account is not linked to a library member.');
    }

    $stmt = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM loans
             WHERE member_id = members.member_id AND status IN ('borrowed', 'overdue')) AS active_loans,
            (SELECT COUNT(*) FROM loans
             WHERE member_id = members.member_id
               AND (status = 'overdue' OR (status = 'borrowed' AND due_date < CURDATE()))) AS overdue_loans,
            (SELECT COUNT(*) FROM reservations
             WHERE member_id = members.member_id AND status = 'pending') AS pending_reservations,
            (SELECT COALESCE(SUM(fines.amount), 0)
             FROM fines INNER JOIN loans ON loans.loan_id = fines.loan_id
             WHERE loans.member_id = members.member_id AND fines.paid = 0) AS unpaid_fines,
            (SELECT COUNT(*) FROM books WHERE available_copies > 0) AS available_titles
         FROM members
         WHERE members.member_id = ? AND members.status = 'active'"
    );
    $stmt->execute([$memberId]);
    $stats = $stmt->fetch();
    if (!$stats) {
        session_destroy();
        header('Location: /library-system/auth/login.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT books.title, loans.due_date,
                CASE
                    WHEN loans.status = 'overdue' OR loans.due_date < CURDATE() THEN 'overdue'
                    ELSE 'borrowed'
                END AS display_status
         FROM loans
         INNER JOIN books ON books.book_id = loans.book_id
         WHERE loans.member_id = ? AND loans.status IN ('borrowed', 'overdue')
         ORDER BY loans.due_date
         LIMIT 6"
    );
    $stmt->execute([$memberId]);
    $myLoans = $stmt->fetchAll();
} else {
    $stats = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM books) AS books,
            (SELECT COUNT(*) FROM members) AS members,
            (SELECT COUNT(*) FROM loans WHERE status IN ('borrowed', 'overdue')) AS active_loans,
            (SELECT COUNT(*) FROM loans
             WHERE status = 'overdue' OR (status = 'borrowed' AND due_date < CURDATE())) AS overdue_loans,
            (SELECT COUNT(*) FROM reservations WHERE status = 'pending') AS pending_reservations,
            (SELECT COALESCE(SUM(amount), 0) FROM fines WHERE paid = 0) AS unpaid_fines"
    )->fetch();
    if ($role === 'admin') {
        $stats['staff_accounts'] = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}

require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>

    <?php if ($isStudent): ?>
        <section class="student-hero">
            <span class="role-badge">STUDENT MEMBER</span>
            <h1>Hello, <?= e($_SESSION['full_name']) ?>.</h1>
            <p>Your reading journey is ready. Browse available titles, check due dates, and manage your reservations.</p>
            <a class="button button-accent" href="/library-system/books/index.php">Explore the catalog <span aria-hidden="true">&rarr;</span></a>
        </section>

        <section class="stats-grid" style="margin-top: 1rem" aria-label="My library summary">
            <article class="card"><h2>Books on loan</h2><p class="stat-value"><?= e($stats['active_loans']) ?></p></article>
            <article class="card"><h2>Overdue</h2><p class="stat-value"><?= e($stats['overdue_loans']) ?></p></article>
            <article class="card"><h2>Pending reservations</h2><p class="stat-value"><?= e($stats['pending_reservations']) ?></p></article>
            <article class="card"><h2>Outstanding fines</h2><p class="stat-value"><?= e(number_format((float)$stats['unpaid_fines'], 2)) ?></p></article>
        </section>

        <section class="card" style="margin-top: 1rem">
            <div class="page-heading">
                <div>
                    <h2>My current loans</h2>
                    <p>Keep an eye on your return dates.</p>
                </div>
                <a href="/library-system/reservations/index.php">My reservations</a>
            </div>
            <?php if (!$myLoans): ?>
                <p class="empty-state">You have no books on loan right now. <a href="/library-system/books/index.php">Find your next read.</a></p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Book</th><th>Due date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($myLoans as $loan): ?>
                            <tr>
                                <td><?= e($loan['title']) ?></td>
                                <td><?= e($loan['due_date']) ?></td>
                                <td><span class="status-pill<?= $loan['display_status'] === 'overdue' ? ' is-overdue' : '' ?>">
                                    <?= e(ucfirst($loan['display_status'])) ?>
                                </span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="welcome-hero">
            <span class="role-badge"><?= e(strtoupper(role_label($role))) ?></span>
            <h1><?= $role === 'admin' ? 'System overview' : 'Your circulation desk' ?></h1>
            <p>
                <?= $role === 'admin'
                    ? 'Welcome back, ' . e($_SESSION['full_name']) . '. Keep the whole library running smoothly.'
                    : 'Welcome back, ' . e($_SESSION['full_name']) . '. Here is what needs attention today.' ?>
            </p>
        </section>

        <section class="stats-grid" style="margin-top: 1rem" aria-label="Library statistics">
            <article class="card"><h2>Books in catalog</h2><p class="stat-value"><?= e($stats['books']) ?></p></article>
            <article class="card"><h2>Members</h2><p class="stat-value"><?= e($stats['members']) ?></p></article>
            <article class="card"><h2>Active loans</h2><p class="stat-value"><?= e($stats['active_loans']) ?></p></article>
            <article class="card"><h2>Overdue loans</h2><p class="stat-value"><?= e($stats['overdue_loans']) ?></p></article>
            <article class="card"><h2>Pending reservations</h2><p class="stat-value"><?= e($stats['pending_reservations']) ?></p></article>
            <article class="card"><h2>Unpaid fines</h2><p class="stat-value"><?= e(number_format((float)$stats['unpaid_fines'], 2)) ?></p></article>
            <?php if ($role === 'admin'): ?>
                <article class="card"><h2>Staff accounts</h2><p class="stat-value"><?= e($stats['staff_accounts']) ?></p></article>
            <?php endif; ?>
        </section>

        <section class="card" style="margin-top: 1rem">
            <div class="page-heading">
                <div>
                    <h2><?= $role === 'admin' ? 'Administration' : 'Daily tasks' ?></h2>
                    <p><?= $role === 'admin'
                        ? 'Manage the catalog, people, and library operations.'
                        : 'Quick access to the circulation desk.' ?></p>
                </div>
            </div>
            <div class="link-grid">
                <a href="/library-system/books/index.php">Browse books</a>
                <a href="/library-system/loans/index.php">Manage loans</a>
                <a href="/library-system/members/index.php">Manage members</a>
                <a href="/library-system/reservations/index.php">View reservations</a>
                <?php if ($role === 'admin'): ?>
                    <a href="/library-system/authors/index.php">Manage authors</a>
                    <a href="/library-system/categories/index.php">Manage categories</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>

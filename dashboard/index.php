<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

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

$pageTitle = 'Dashboard';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <div>
            <h1>Welcome, <?= e($_SESSION['full_name'] ?? $_SESSION['username']) ?></h1>
            <p>Library activity at a glance.</p>
        </div>
    </header>

    <section class="stats-grid" aria-label="Library statistics">
        <article class="card"><h2>Books</h2><p class="stat-value"><?= e($stats['books']) ?></p></article>
        <article class="card"><h2>Members</h2><p class="stat-value"><?= e($stats['members']) ?></p></article>
        <article class="card"><h2>Active loans</h2><p class="stat-value"><?= e($stats['active_loans']) ?></p></article>
        <article class="card"><h2>Overdue loans</h2><p class="stat-value"><?= e($stats['overdue_loans']) ?></p></article>
        <article class="card"><h2>Pending reservations</h2><p class="stat-value"><?= e($stats['pending_reservations']) ?></p></article>
        <article class="card"><h2>Unpaid fines</h2><p class="stat-value"><?= e(number_format((float)$stats['unpaid_fines'], 2)) ?></p></article>
    </section>

    <section class="card" style="margin-top: 1rem">
        <h2>Library management</h2>
        <div class="link-grid">
            <a href="/library-system/books/index.php">Browse books</a>
            <a href="/library-system/loans/index.php">Manage loans</a>
            <a href="/library-system/members/index.php">Manage members</a>
            <a href="/library-system/reservations/index.php">View reservations</a>
        </div>
    </section>
</main>
<?php require '../includes/footer.php'; ?>
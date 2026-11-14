<?php if (isset($_SESSION['user_id'])): ?>
<nav class="site-nav" aria-label="Main navigation">
    <a href="/library-system/dashboard/index.php">Dashboard</a>
    <a href="/library-system/books/index.php">Books</a>
    <a href="/library-system/authors/index.php">Authors</a>
    <a href="/library-system/categories/index.php">Categories</a>
    <a href="/library-system/members/index.php">Members</a>
    <a href="/library-system/loans/index.php">Loans</a>
    <a href="/library-system/reservations/index.php">Reservations</a>
    <a href="/library-system/fines/index.php">Fines</a>
    <span class="nav-user"><?= e($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
    <form class="nav-logout" action="/library-system/auth/logout.php" method="post">
        <?= csrf_field() ?>
        <button class="button button-secondary" type="submit">Sign out</button>
    </form>
</nav>
<?php endif; ?>
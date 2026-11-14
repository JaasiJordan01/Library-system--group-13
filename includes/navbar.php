<?php
$role = $_SESSION['role'] ?? 'librarian';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$dashboard = '/library-system/dashboard/index.php';
$books = '/library-system/books/index.php';
$reservations = '/library-system/reservations/index.php';
$navItems = [
    ['label' => 'Dashboard', 'href' => $dashboard, 'icon' => 'DB'],
    ['label' => $role === 'student' ? 'Browse books' : 'Books', 'href' => $books, 'icon' => 'BK'],
];

if ($role === 'student') {
    $navItems[] = ['label' => 'My reservations', 'href' => $reservations, 'icon' => 'RS'];
} else {
    if ($role === 'admin') {
        $navItems[] = ['label' => 'Authors', 'href' => '/library-system/authors/index.php', 'icon' => 'AU'];
        $navItems[] = ['label' => 'Categories', 'href' => '/library-system/categories/index.php', 'icon' => 'CT'];
    }
    $navItems[] = ['label' => 'Members', 'href' => '/library-system/members/index.php', 'icon' => 'MB'];
    $navItems[] = ['label' => 'Loans', 'href' => '/library-system/loans/index.php', 'icon' => 'LN'];
    $navItems[] = ['label' => 'Reservations', 'href' => $reservations, 'icon' => 'RS'];
    $navItems[] = ['label' => 'Fines', 'href' => '/library-system/fines/index.php', 'icon' => 'FN'];
}
?>
<aside class="sidebar">
    <div class="sidebar-head">
        <a class="brand" href="<?= e($dashboard) ?>">
            <span class="brand-mark" aria-hidden="true">LS</span>
            <span class="brand-copy">
                <strong>Library System</strong>
                <span>Read. Discover. Grow.</span>
            </span>
        </a>
        <button class="sidebar-menu-toggle" type="button" data-menu-toggle
                aria-controls="sidebar-content" aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="sidebar-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
            <span data-menu-label>Menu</span>
        </button>
    </div>

    <div class="sidebar-content" id="sidebar-content">
        <p class="sidebar-label">WORKSPACE</p>
        <nav class="sidebar-nav" aria-label="Main navigation">
            <?php foreach ($navItems as $item): ?>
                <?php
                $active = $currentPath === $item['href']
                    || dirname($currentPath) === dirname($item['href']);
                ?>
                <a class="sidebar-link<?= $active ? ' is-active' : '' ?>"
                   href="<?= e($item['href']) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                    <span class="sidebar-icon" aria-hidden="true"><?= e($item['icon']) ?></span>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom">
            <div class="account-card">
                <span class="account-avatar"><?= e(strtoupper(substr($_SESSION['full_name'] ?? 'L', 0, 1))) ?></span>
                <span class="account-details">
                    <strong><?= e($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong>
                    <small><?= e(role_label($role)) ?></small>
                </span>
            </div>
            <form action="/library-system/auth/logout.php" method="post">
                <?= csrf_field() ?>
                <button class="sidebar-signout" type="submit">Sign out</button>
            </form>
        </div>
    </div>
</aside>

<div class="main-panel">
    <header class="topbar">
        <div>
            <span class="topbar-kicker">LIBRARY WORKSPACE</span>
            <strong><?= e($pageTitle) ?></strong>
        </div>
        <span class="role-badge"><?= e(role_label($role)) ?></span>
    </header>
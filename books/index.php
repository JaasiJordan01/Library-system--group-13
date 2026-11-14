<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$stmt = $pdo->query(
    'SELECT books.book_id, books.isbn, books.title, books.cover_isbn, authors.name AS author_name
     FROM books
     INNER JOIN authors ON authors.author_id = books.author_id
     ORDER BY books.title'
);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Books | Library System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/books-3d.css">
    <script src="../assets/js/app.js" defer></script>
    <style>
        body {
            margin: 0;
            padding: 2rem;
            color: #243447;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }
        main {
            max-width: 1100px;
            margin: 0 auto;
        }
        .page-heading {
            margin-bottom: 2rem;
        }
        .page-heading h1 {
            margin: 0;
        }
        .book-container {
            height: auto;
            min-height: 320px;
            text-align: center;
        }
        .volume {
            background: #dce3e9;
        }
        .volume > img {
            display: block;
        }
        .volume.cover-unavailable > img {
            display: none;
        }
        .cover-unavailable-label {
            display: none;
            padding-top: 7rem;
            color: #526170;
        }
        .volume.cover-unavailable .cover-unavailable-label {
            display: block;
        }
        .book-details {
            margin-top: 1.25rem;
        }
        .book-actions {
            justify-content: center;
            margin-top: 0.6rem;
        }
        .empty-state {
            padding: 2rem;
            text-align: center;
            background: #fff;
            border-radius: 6px;
        }
        @media (max-width: 520px) {
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<nav class="site-nav" aria-label="Main navigation">
    <a href="../dashboard/index.php">Dashboard</a>
    <a href="index.php" aria-current="page">Books</a>
    <a href="../authors/index.php">Authors</a>
    <a href="../categories/index.php">Categories</a>
    <a href="../members/index.php">Members</a>
    <a href="../loans/index.php">Loans</a>
    <a href="../reservations/index.php">Reservations</a>
    <a href="../fines/index.php">Fines</a>
    <form class="nav-logout" action="../auth/logout.php" method="post">
        <?= csrf_field() ?>
        <button class="button button-secondary" type="submit">Sign out</button>
    </form>
</nav>
<main>
    <?php show_flash(); ?>
    <header class="page-heading">
        <h1>Books</h1>
    </header>

    <?php if (!$books): ?>
        <p class="empty-state">No books have been added to the library yet.</p>
    <?php else: ?>
        <section class="book-grid" aria-label="Library book collection">
            <?php foreach ($books as $book): ?>
                <?php
                $coverIsbn = str_replace(
                    ['-', ' '],
                    '',
                    trim((string)($book['cover_isbn'] ?: $book['isbn']))
                );
                $hasValidCoverIsbn = preg_match('/\A[0-9Xx]{10,13}\z/', $coverIsbn) === 1;
                ?>
                <article class="book-container">
                    <div class="volume<?= $hasValidCoverIsbn ? '' : ' cover-unavailable' ?>">
                        <?php if ($hasValidCoverIsbn): ?>
                            <img
                                src="https://covers.openlibrary.org/b/isbn/<?= e($coverIsbn) ?>-M.jpg"
                                alt="Cover of <?= e($book['title']) ?>"
                                loading="lazy"
                                onerror="this.onerror=null; this.parentElement.classList.add('cover-unavailable')">
                        <?php endif; ?>
                        <span class="cover-unavailable-label" aria-hidden="true">Cover unavailable</span>
                    </div>
                    <div class="book-details">
                        <strong><?= e($book['title']) ?></strong><br>
                        <span><?= e($book['author_name']) ?></span>
                        <div class="actions book-actions">
                            <a class="button button-secondary" href="edit.php?id=<?= e($book['book_id']) ?>">Edit</a>
                            <form class="inline-form" method="post" action="delete.php"
                                  data-confirm="Delete this book?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="book_id" value="<?= e($book['book_id']) ?>">
                                <button class="button button-danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>

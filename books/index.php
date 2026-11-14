<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT books.book_id, books.isbn, books.title, books.cover_isbn,
               books.available_copies, books.total_copies,
               authors.name AS author_name
        FROM books
        INNER JOIN authors ON authors.author_id = books.author_id';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE books.title LIKE ? OR books.isbn LIKE ? OR authors.name LIKE ?';
    $pattern = '%' . $search . '%';
    $params = [$pattern, $pattern, $pattern];
}

$sql .= ' ORDER BY books.title';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
$isStudent = ($_SESSION['role'] ?? '') === 'student';
$pageTitle = $isStudent ? 'Browse books' : 'Books';
$extraStylesheet = '/library-system/assets/css/books-3d.css?v=20261114b';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container books-main">
    <?php show_flash(); ?>
    <header class="page-heading">
        <div>
            <h1><?= $isStudent ? 'Explore the collection' : 'Books' ?></h1>
            <p><?= $isStudent
                ? 'Search the library and see what is ready to borrow.'
                : 'Browse the collection and manage book details.' ?></p>
        </div>
        <?php if (!$isStudent): ?>
            <a class="button button-accent" href="create.php">Add a book</a>
        <?php endif; ?>
    </header>

    <form class="catalog-search" method="get" role="search">
        <label class="sr-only" for="book-search">Search books</label>
        <input id="book-search" name="q" type="search" value="<?= e($search) ?>"
               placeholder="Search title, author, or ISBN">
        <button class="button" type="submit">Search</button>
        <?php if ($search !== ''): ?>
            <a class="button button-secondary" href="index.php">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$books): ?>
        <p class="card empty-state">
            <?= $search !== '' ? 'No books matched your search.' : 'No books have been added to the library yet.' ?>
        </p>
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
                        <strong><?= e($book['title']) ?></strong>
                        <span class="book-author"><?= e($book['author_name']) ?></span>
                        <span class="book-availability<?= (int)$book['available_copies'] > 0 ? ' is-available' : ' is-unavailable' ?>">
                            <?= (int)$book['available_copies'] > 0
                                ? e($book['available_copies']) . ' of ' . e($book['total_copies']) . ' available'
                                : 'Currently unavailable' ?>
                        </span>
                        <div class="actions book-actions">
                            <?php if ($isStudent && (int)$book['available_copies'] === 0): ?>
                                <a class="button button-accent"
                                   href="../reservations/create.php?book_id=<?= e($book['book_id']) ?>">Reserve a copy</a>
                            <?php elseif (!$isStudent): ?>
                                <a class="button button-secondary" href="edit.php?id=<?= e($book['book_id']) ?>">Edit</a>
                                <form class="inline-form" method="post" action="delete.php"
                                      data-confirm="Delete this book?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="book_id" value="<?= e($book['book_id']) ?>">
                                    <button class="button button-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>

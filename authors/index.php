<?php
require_once '../includes/auth_check.php';
require_roles(['admin']);
require_once '../config/database.php';
require_once '../includes/functions.php';

$authors = $pdo->query(
    'SELECT authors.author_id, authors.name, authors.country, COUNT(books.book_id) AS book_count
     FROM authors
     LEFT JOIN books ON books.author_id = authors.author_id
     GROUP BY authors.author_id, authors.name, authors.country
     ORDER BY authors.name'
)->fetchAll();

$pageTitle = 'Authors';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <h1>Authors</h1>
        <a class="button" href="create.php">Add author</a>
    </header>
    <?php if (!$authors): ?>
        <p class="card empty-state">No authors have been added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Country</th><th>Books</th></tr></thead>
                <tbody>
                <?php foreach ($authors as $author): ?>
                    <tr>
                        <td><?= e($author['name']) ?></td>
                        <td><?= e($author['country'] ?: '—') ?></td>
                        <td><?= e($author['book_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>
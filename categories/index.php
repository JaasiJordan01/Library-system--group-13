<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$categories = $pdo->query(
    'SELECT categories.category_id, categories.name, categories.description,
            COUNT(books.book_id) AS book_count
     FROM categories
     LEFT JOIN books ON books.category_id = categories.category_id
     GROUP BY categories.category_id, categories.name, categories.description
     ORDER BY categories.name'
)->fetchAll();

$pageTitle = 'Categories';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <h1>Categories</h1>
        <a class="button" href="create.php">Add category</a>
    </header>
    <?php if (!$categories): ?>
        <p class="card empty-state">No categories have been added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Description</th><th>Books</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['name']) ?></td>
                        <td><?= e($category['description'] ?: '—') ?></td>
                        <td><?= e($category['book_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>
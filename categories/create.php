<?php
require_once '../includes/auth_check.php';
require_roles(['admin']);
require_once '../config/database.php';
require_once '../includes/functions.php';

$errors = [];
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    if ($name === '') {
        $errors[] = 'Category name is required.';
    } elseif (text_length($name) > 60) {
        $errors[] = 'Category name must be 60 characters or fewer.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
            $stmt->execute([$name, $description !== '' ? $description : null]);
            set_flash('success', 'Category added.');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'A category with that name already exists.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Add category';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <header class="page-heading"><h1>Add category</h1></header>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form class="card form-grid" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" maxlength="60" required value="<?= e($name) ?>">
        </div>
        <div class="field field-full">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description"><?= e($description) ?></textarea>
        </div>
        <div class="field-full form-actions">
            <button class="button" type="submit">Save category</button>
            <a class="button button-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</main>
<?php require '../includes/footer.php'; ?>

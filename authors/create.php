<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$errors = [];
$name = '';
$country = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    if ($name === '') {
        $errors[] = 'Author name is required.';
    } elseif (text_length($name) > 100) {
        $errors[] = 'Author name must be 100 characters or fewer.';
    }
    if (text_length($country) > 60) {
        $errors[] = 'Country must be 60 characters or fewer.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO authors (name, country) VALUES (?, ?)');
        $stmt->execute([$name, $country !== '' ? $country : null]);
        set_flash('success', 'Author added.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Add author';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <header class="page-heading"><h1>Add author</h1></header>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form class="card form-grid" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" maxlength="100" required value="<?= e($name) ?>">
        </div>
        <div class="field">
            <label for="country">Country (optional)</label>
            <input id="country" name="country" maxlength="60" value="<?= e($country) ?>">
        </div>
        <div class="field-full form-actions">
            <button class="button" type="submit">Save author</button>
            <a class="button button-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</main>
<?php require '../includes/footer.php'; ?>
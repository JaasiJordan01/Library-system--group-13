<?php
require_once '../includes/auth_check.php';
require_roles(['admin', 'librarian']);
require_once '../config/database.php';
require_once '../includes/functions.php';

$members = $pdo->query(
    'SELECT member_id, member_number, full_name, email, phone, status, date_joined,
            password_hash
     FROM members
     ORDER BY full_name'
)->fetchAll();

$pageTitle = 'Members';
require '../includes/header.php';
require '../includes/navbar.php';
?>
<main class="container">
    <?php show_flash(); ?>
    <header class="page-heading">
        <h1>Members</h1>
        <a class="button" href="create.php">Add member</a>
    </header>
    <?php if (!$members): ?>
        <p class="card empty-state">No members have been added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Number</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Portal access</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= e($member['member_number']) ?></td>
                        <td><?= e($member['full_name']) ?></td>
                        <td><?= e($member['email']) ?></td>
                        <td><?= e($member['phone'] ?: '—') ?></td>
                        <td><?= e(ucfirst($member['status'])) ?></td>
                        <td><?= $member['password_hash'] ? 'Student enabled' : 'Not set' ?></td>
                        <td><?= e($member['date_joined']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="button button-secondary" href="edit.php?id=<?= e($member['member_id']) ?>">Edit</a>
                                <form class="inline-form" method="post" action="delete.php"
                                      data-confirm="Delete this member?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="member_id" value="<?= e($member['member_id']) ?>">
                                    <button class="button button-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php require '../includes/footer.php'; ?>
<?php
/**
 * Neuromax – Admin Users Management
 * 
 * List, search, toggle role, and delete users.
 */

require_once __DIR__ . '/../app/controllers/AdminController.php';

$admin = new AdminController();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'delete':
            $admin->deleteUser();
            exit;
        case 'toggle_role':
            $admin->toggleRole();
            exit;
    }
}

require_once __DIR__ . '/../app/models/User.php';
$userModel = new User();
$search = trim($_GET['search'] ?? '');
$users = $userModel->getAll($search);

$pageTitle = 'Users';
require_once __DIR__ . '/../templates/admin_header.php';
?>

<div class="page-header">
    <h1>User Management</h1>
    <p>View and manage all registered users.</p>
</div>

<div class="page-content">

    <!-- Search -->
    <div class="card mb-3" style="padding: 16px;">
        <form method="GET" action="" class="flex gap-1">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                   value="<?= e($search) ?>" style="max-width: 400px;">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search" style="width:16px;"></i> Search</button>
            <?php if ($search): ?>
                <a href="<?= url('admin/users.php') ?>" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 40px;">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= (int)$user['id'] ?></td>
                            <td><strong><?= e($user['name']) ?></strong></td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <span class="badge <?= $user['role'] === 'admin' ? 'badge-info' : 'badge-secondary' ?>">
                                    <?= e(ucfirst($user['role'])) ?>
                                </span>
                            </td>
                            <td class="text-sm"><?= formatDate($user['created_at']) ?></td>
                            <td>
                                <div class="flex gap-1">
                                    <form method="POST" action="" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Toggle role"
                                                onclick="return confirm('Toggle role for <?= e($user['name']) ?>?')">
                                            <i data-lucide="refresh-cw" style="width:16px;"></i>
                                        </button>
                                    </form>
                                    <?php if ((int)$user['id'] !== currentUserId()): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete user"
                                                onclick="return confirm('Delete user <?= e($user['name']) ?>? This cannot be undone.')">
                                            <i data-lucide="trash-2" style="width:16px;"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-sm text-muted mt-2">Total: <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?></p>

</div>

<?php require_once __DIR__ . '/../templates/admin_footer.php'; ?>

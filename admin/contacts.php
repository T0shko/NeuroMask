<?php
/**
 * Neuromax – Admin Contacts Management
 * 
 * View and audit contact form submissions.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../app/models/Contact.php';

requireAdmin();

$contactModel = new Contact();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $contactModel->markRead($id);
            setFlash('success', 'Message marked as read.');
        }
        redirect(url('admin/contacts.php'));
        exit;
    }
}

$contacts = $contactModel->getAll();

$pageTitle = 'Contacts';
require_once __DIR__ . '/../templates/admin_header.php';
?>

<div class="page-header">
    <h1>Contact Messages</h1>
    <p>Audit and manage contact form submissions.</p>
</div>

<div class="page-content">

    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding: 40px;">No messages found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                        <tr style="<?= !(int)$contact['is_read'] ? 'font-weight: 600; background: rgba(59, 130, 246, 0.05);' : '' ?>">
                            <td class="text-sm" style="white-space: nowrap;"><?= formatDate($contact['created_at']) ?></td>
                            <td style="white-space: nowrap;"><?= e($contact['name']) ?></td>
                            <td><a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a></td>
                            <td><?= e($contact['subject']) ?></td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($contact['message']) ?>">
                                <?= e($contact['message']) ?>
                            </td>
                            <td>
                                <?php if ((int)$contact['is_read']): ?>
                                    <span class="badge badge-secondary">Read</span>
                                <?php else: ?>
                                    <span class="badge badge-info">New</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!(int)$contact['is_read']): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?= (int)$contact['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Mark as Read">
                                        ✓ Mark Read
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/admin_footer.php'; ?>

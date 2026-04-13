<?php
/**
 * Neuromax – Admin Jobs Management
 * 
 * View, filter, and delete processing jobs.
 */

require_once __DIR__ . '/../app/controllers/AdminController.php';

$admin = new AdminController();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $admin->deleteJob();
    exit;
}

require_once __DIR__ . '/../app/models/Job.php';
$jobModel = new Job();

$statusFilter = $_GET['status'] ?? '';
$jobs = $jobModel->getAll($statusFilter);

$pageTitle = 'Jobs';
require_once __DIR__ . '/../templates/admin_header.php';
?>

<div class="page-header">
    <h1>Job Management</h1>
    <p>View and manage all AI processing jobs.</p>
</div>

<div class="page-content">

    <!-- Status Filter Tabs -->
    <div class="flex gap-1 mb-3">
        <a href="<?= url('admin/jobs.php') ?>" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">All</a>
        <a href="<?= url('admin/jobs.php?status=pending') ?>" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">⏳ Pending</a>
        <a href="<?= url('admin/jobs.php?status=processing') ?>" class="btn <?= $statusFilter === 'processing' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">🔄 Processing</a>
        <a href="<?= url('admin/jobs.php?status=completed') ?>" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">✅ Completed</a>
        <a href="<?= url('admin/jobs.php?status=failed') ?>" class="btn <?= $statusFilter === 'failed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">❌ Failed</a>
    </div>

    <!-- Jobs Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Source Face</th>
                        <th>Target Photo</th>
                        <th>Result</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 40px;">No jobs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>#<?= (int)$job['id'] ?></td>
                            <td>
                                <strong><?= e($job['user_name']) ?></strong>
                                <div class="text-sm text-muted"><?= e($job['user_email']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($job['source_path'])): ?>
                                    <img src="<?= assetUrl('uploads/' . e($job['source_path'])) ?>" alt="Source"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($job['file_path']): ?>
                                    <img src="<?= assetUrl('uploads/' . e($job['file_path'])) ?>" alt="Target"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($job['result_path']): ?>
                                    <img src="<?= assetUrl('results/' . e($job['result_path'])) ?>" alt="Result"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= statusBadgeClass($job['status']) ?>"><?= e(ucfirst($job['status'])) ?></span></td>
                            <td class="text-sm"><?= formatDateTime($job['created_at']) ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this job?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-sm text-muted mt-2">Showing <?= count($jobs) ?> job<?= count($jobs) !== 1 ? 's' : '' ?></p>

</div>

<?php require_once __DIR__ . '/../templates/admin_footer.php'; ?>

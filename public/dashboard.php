<?php
/**
 * Neuromax – User Dashboard
 * 
 * Main dashboard showing stats, recent jobs, and quick actions.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Job.php';
require_once __DIR__ . '/../app/models/Subscription.php';

$userModel = new User();
$jobModel = new Job();
$subModel = new Subscription();

$userId = currentUserId();
$userInfo = $userModel->getUserWithSubscription($userId);
$stats = $jobModel->getStats($userId);
$recentJobs = $jobModel->findByUser($userId, 5);
$currentPlan = $subModel->getUserSubscription($userId);

$pageTitle = 'Dashboard';
$pageDescription = 'Your Neuromax dashboard overview';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>Welcome back, <?= e(currentUserName()) ?> 👋</h1>
    <p>Here's an overview of your face swap activity.</p>
</div>

<div class="page-content">

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">⚡</div>
            <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Jobs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-value"><?= (int)($stats['completed'] ?? 0) ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div class="stat-value"><?= (int)($stats['pending'] ?? 0) ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">💎</div>
            <div class="stat-value"><?= e($currentPlan['name'] ?? 'Basic') ?></div>
            <div class="stat-label">Current Plan</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex gap-2 mb-3">
        <a href="<?= publicUrl('upload.php') ?>" class="btn btn-primary">
            🔄 New Face Swap
        </a>
        <a href="<?= publicUrl('plans.php') ?>" class="btn btn-secondary">
            💎 View Plans
        </a>
    </div>

    <!-- Recent Jobs -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Jobs</h3>
            <a href="<?= publicUrl('jobs.php') ?>" class="btn btn-secondary btn-sm">View All</a>
        </div>

        <?php if (empty($recentJobs)): ?>
            <div class="empty-state">
                <span class="empty-icon">📭</span>
                <h3>No face swaps yet</h3>
                <p>Start your first face swap to see results!</p>
                <a href="<?= publicUrl('upload.php') ?>" class="btn btn-primary">Start Face Swap</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Source</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentJobs as $job): ?>
                        <tr>
                            <td>#<?= (int)$job['id'] ?></td>
                            <td>
                                <img src="<?= assetUrl('uploads/' . e($job['source_path'])) ?>" alt="Source"
                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                            </td>
                            <td>
                                <img src="<?= assetUrl('uploads/' . e($job['file_path'])) ?>" alt="Target"
                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                            </td>
                            <td><span class="badge <?= statusBadgeClass($job['status']) ?>"><?= e(ucfirst($job['status'])) ?></span></td>
                            <td><?= formatDate($job['created_at']) ?></td>
                            <td>
                                <?php if ($job['status'] === 'completed' && $job['result_path']): ?>
                                    <a href="<?= publicUrl('jobs.php?view=' . (int)$job['id']) ?>" class="btn btn-sm btn-outline">View</a>
                                <?php elseif ($job['status'] === 'failed'): ?>
                                    <span class="text-sm" style="color: var(--accent-red);">⚠️ Failed</span>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

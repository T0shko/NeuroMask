<?php
/**
 * Neuromax – Admin Dashboard
 * 
 * Overview of platform stats: users, jobs, subscriptions.
 */

require_once __DIR__ . '/../app/controllers/AdminController.php';

$admin = new AdminController();
$stats = $admin->getDashboardStats();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../templates/admin_header.php';
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>Platform overview and management.</p>
</div>

<div class="page-content">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i data-lucide="users"></i></div>
            <div class="stat-value"><?= (int)$stats['total_users'] ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i data-lucide="zap"></i></div>
            <div class="stat-value"><?= (int)($stats['job_stats']['total'] ?? 0) ?></div>
            <div class="stat-label">Total Jobs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
            <div class="stat-value"><?= (int)($stats['job_stats']['completed'] ?? 0) ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i data-lucide="clock"></i></div>
            <div class="stat-value"><?= (int)($stats['job_stats']['pending'] ?? 0) ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan"><i data-lucide="mail"></i></div>
            <div class="stat-value"><?= (int)$stats['unread_msgs'] ?></div>
            <div class="stat-label">Unread Messages</div>
        </div>
    </div>

    <!-- Subscription Distribution -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Subscriber Distribution</h3>
            </div>
            <?php if (!empty($stats['plan_stats'])): ?>
                <?php foreach ($stats['plan_stats'] as $plan): ?>
                <div class="flex-between mb-2" style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <strong><?= e($plan['name']) ?></strong>
                        <span class="text-muted text-sm"> · $<?= number_format((float)$plan['price'], 2) ?></span>
                    </div>
                    <span class="badge badge-info"><?= (int)$plan['subscriber_count'] ?> users</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="<?= url('admin/users.php') ?>" class="btn btn-secondary"><i data-lucide="users" style="width: 16px;"></i> Manage Users</a>
                <a href="<?= url('admin/jobs.php') ?>" class="btn btn-secondary"><i data-lucide="zap" style="width: 16px;"></i> Manage Jobs</a>
                <a href="<?= url('admin/subscriptions.php') ?>" class="btn btn-secondary"><i data-lucide="gem" style="width: 16px;"></i> Manage Plans</a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/admin_footer.php'; ?>

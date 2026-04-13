<?php
/**
 * Neuromax – Admin Subscriptions Management
 * 
 * Edit subscription plans.
 */

require_once __DIR__ . '/../app/controllers/AdminController.php';

$admin = new AdminController();

// Handle plan update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin->updatePlan();
    exit;
}

require_once __DIR__ . '/../app/models/Subscription.php';
$subModel = new Subscription();
$plans = $subModel->getSubscriberCounts();

$pageTitle = 'Subscriptions';
require_once __DIR__ . '/../templates/admin_header.php';
?>

<div class="page-header">
    <h1>Subscription Plans</h1>
    <p>Manage subscription plans and pricing.</p>
</div>

<div class="page-content">

    <div class="pricing-grid" style="max-width: 100%;">
        <?php foreach ($plans as $plan):
            $features = json_decode($plan['features'] ?? '[]', true) ?: [];
            // Fetch full plan data
            $fullPlan = $subModel->findById((int)$plan['id']);
        ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= e($plan['name']) ?></h3>
                <span class="badge badge-info"><?= (int)$plan['subscriber_count'] ?> users</span>
            </div>

            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">

                <div class="form-group">
                    <label class="form-label">Plan Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($plan['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Price ($/month)</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0"
                           value="<?= number_format((float)$plan['price'], 2, '.', '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Max Jobs/Month</label>
                    <input type="number" name="max_jobs" class="form-control" min="1"
                           value="<?= (int)($fullPlan['max_jobs'] ?? 10) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Features (JSON array)</label>
                    <textarea name="features" class="form-control" rows="4" required><?= e(json_encode($features, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
                    <div class="form-text">Enter as a JSON array, e.g. ["Feature 1", "Feature 2"]</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">💾 Save Changes</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/admin_footer.php'; ?>

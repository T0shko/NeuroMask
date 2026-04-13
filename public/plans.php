<?php
/**
 * Neuromax – Subscription Plans Page
 * 
 * Display available plans and allow users to select/change.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../app/models/Subscription.php';

// Handle plan selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/SubscriptionController.php';
    $controller = new SubscriptionController();
    $controller->selectPlan();
    exit;
}

$subModel = new Subscription();
$plans = $subModel->getAll();
$currentSub = $subModel->getUserSubscription(currentUserId());
$currentPlanId = $currentSub ? (int)$currentSub['id'] : 1;

$pageTitle = 'Plans';
$pageDescription = 'Choose your subscription plan';
require_once __DIR__ . '/../templates/header.php';

$planIcons = ['🆓', '🚀', '👑'];
$planColors = [
    'rgba(107, 114, 128, 0.15)',
    'rgba(59, 130, 246, 0.15)',
    'rgba(139, 92, 246, 0.15)',
];
?>

<div class="page-header">
    <h1>Subscription Plans</h1>
    <p>Choose the plan that best fits your needs. You can upgrade or downgrade anytime.</p>
</div>

<div class="page-content">

    <?php if ($currentSub): ?>
    <div class="alert alert-info mb-3">
        <span>You are currently on the <strong><?= e($currentSub['name']) ?></strong> plan (active since <?= formatDate($currentSub['start_date']) ?>)</span>
    </div>
    <?php endif; ?>

    <div class="pricing-grid">
        <?php foreach ($plans as $index => $plan):
            $isCurrent = ($currentPlanId === (int)$plan['id']);
            $isFeatured = ($index === 1); // Pro plan
            $features = json_decode($plan['features'], true) ?: [];
        ?>
        <div class="pricing-card <?= $isFeatured ? 'featured' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
            <div class="plan-icon" style="background: <?= $planColors[$index] ?? '' ?>">
                <?= $planIcons[$index] ?? '💎' ?>
            </div>
            <div class="plan-name"><?= e($plan['name']) ?></div>
            <div class="plan-price">
                $<?= number_format((float)$plan['price'], 0) ?><span><?= (float)$plan['price'] > 0 ? '.99/mo' : '/mo' ?></span>
            </div>
            <div class="plan-description">
                Up to <?= (int)$plan['max_jobs'] >= 9999 ? 'unlimited' : (int)$plan['max_jobs'] ?> transformations/month
            </div>
            <ul class="plan-features">
                <?php foreach ($features as $feature): ?>
                <li><?= e($feature) ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($isCurrent): ?>
                <button class="btn btn-success btn-block" disabled>✅ Current Plan</button>
            <?php else: ?>
                <form method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                    <button type="submit" class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline' ?> btn-block"
                            onclick="return confirm('Switch to <?= e($plan['name']) ?> plan?')">
                        Select <?= e($plan['name']) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

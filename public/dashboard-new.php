<?php
/**
 * NeuroMask – User Dashboard
 * Concept: The Laboratory Workspace
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

require_once __DIR__ . '/../templates/dashboard-new.php';
?>

<!-- Canvas Header -->
<div class="canvas-header">
    <h1>Welcome, <span><?= e(currentUserName()) ?></span></h1>
</div>

<!-- Canvas Content -->
<div class="canvas-content">

    <!-- Laboratory Status -->
    <div class="laboratory-status">
        <div class="laboratory-status-header">
            <div class="laboratory-greeting">
                Welcome back, <span><?= e(currentUserName()) ?></span>
            </div>
            <div class="laboratory-action">
                Your laboratory is active
            </div>
        </div>

        <!-- Biometric Statistics -->
        <div class="biometric-stats">
            <div class="stat-biometric">
                <span class="stat-glyph">◆</span>
                <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="stat-label">Total Transmutations</div>
            </div>
            <div class="stat-biometric">
                <span class="stat-glyph">◉</span>
                <div class="stat-value"><?= (int)($stats['completed'] ?? 0) ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-biometric">
                <span class="stat-glyph">◈</span>
                <div class="stat-value"><?= e($currentPlan['name'] ?? 'Experiment') ?></div>
                <div class="stat-label">Current Access Tier</div>
            </div>
        </div>
    </div>

    <!-- Laboratory Controls -->
    <div class="laboratory-controls">
        <div class="controls-header">
            <h2 class="controls-title">Laboratory Controls</h2>
            <p class="controls-subtitle">Quick actions at your disposal</p>
        </div>

        <div class="controls-grid">
            <a href="<?= publicUrl('upload.php') ?>" class="control-trigger">
                <span class="trigger-icon">◈</span>
                <div class="trigger-label">Initiate Transmutation</div>
                <div class="trigger-description">Begin a new identity transformation</div>
            </a>

            <a href="<?= publicUrl('jobs.php') ?>" class="control-trigger">
                <span class="trigger-icon">◇</span>
                <div class="trigger-label">Browse Specimens</div>
                <div class="trigger-description">View all your completed work</div>
            </a>
        </div>
    </div>

    <!-- Transformations Gallery -->
    <div class="transformations-gallery">
        <div class="gallery-header">
            <h2 class="gallery-title">Recent Specimens</h2>
            <div class="gallery-actions">
                <a href="<?= publicUrl('jobs.php') ?>" class="btn-identity btn-ghost-identity">
                    View All
                    <span>→</span>
                </a>
            </div>
        </div>

        <?php if (empty($recentJobs)): ?>
        <div class="gallery-empty">
            <span class="gallery-empty-icon">◐</span>
            <div class="gallery-empty-text">No specimens in the archive</div>
            <div class="gallery-empty-subtext">Initiate your first transmutation to begin the collection</div>
        </div>
        <?php else: ?>
        <div class="gallery-view">
            <?php foreach ($recentJobs as $job): ?>
            <div class="transformation-specimen">
                <div class="specimen-visual">
                    <?php if ($job['result_path']): ?>
                    <img src="<?= assetUrl('results/' . e($job['result_path'])) ?>" alt="Result">
                    <?php elseif ($job['file_path']): ?>
                    <img src="<?= assetUrl('uploads/' . e($job['file_path'])) ?>" alt="Target">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background:var(--void-black);display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:64px;opacity:0.2;">◐</span>
                    </div>
                    <?php endif; ?>

                    <div class="specimen-status <?= $job['status'] ?>">
                        <?php
                        $statusLabels = [
                            'completed' => 'Complete',
                            'processing' => 'Processing',
                            'pending' => 'Pending',
                            'failed' => 'Failed'
                        ];
                        echo e($statusLabels[$job['status']] ?? ucfirst($job['status']));
                        ?>
                    </div>
                </div>

                <div class="specimen-details">
                    <div class="specimen-id">Specimen #<?= (int)$job['id'] ?></div>
                    <div class="specimen-timestamp"><?= formatDate($job['created_at']) ?></div>

                    <div class="specimen-actions">
                        <?php if ($job['status'] === 'completed' && $job['result_path']): ?>
                        <a href="<?= publicUrl('jobs.php?view=' . (int)$job['id']) ?>" class="specimen-action-trigger">
                            Examine
                        </a>
                        <a href="<?= assetUrl('results/' . e($job['result_path'])) ?>" class="specimen-action-trigger" download>
                            Acquire
                        </a>
                        <?php elseif ($job['status'] === 'failed'): ?>
                        <span class="specimen-action-trigger" style="color:var(--identity-rose);">
                            Failed
                        </span>
                        <?php else: ?>
                        <span class="specimen-action-trigger">
                            Processing
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Subscription Indicator -->
    <?php if ($currentPlan): ?>
    <div class="subscription-indicator">
        <div class="subscription-info">
            <div class="subscription-badge">
                ◑
            </div>
            <div>
                <h3 class="subscription-tier"><?= e($currentPlan['name']) ?></h3>
                <div class="subscription-cycle">
                    Access level
                </div>
            </div>
        </div>

        <div class="subscription-usage">
            <div class="usage-gauge">
                <div class="usage-header">
                    <span class="usage-label">This Cycle</span>
                    <span class="usage-value"><?= $currentPlan['used_count'] ?? 0 ?>/<?= $currentPlan['swaps_allowed'] ?? '∞' ?></span>
                </div>
                <div class="usage-track">
                    <?php
                    $usagePercent = 0;
                    if ($currentPlan['swaps_allowed'] && $currentPlan['swaps_allowed'] > 0) {
                        $usagePercent = (($currentPlan['used_count'] ?? 0) / $currentPlan['swaps_allowed']) * 100;
                    } elseif ($currentPlan['swaps_allowed'] === -1) {
                        $usagePercent = 100;
                    }
                    ?>
                    <div class="usage-fill" style="width: <?= $usagePercent ?>%;"></div>
                </div>
            </div>

            <a href="<?= publicUrl('plans.php') ?>" class="btn-identity btn-ghost-identity">
                Modify Access
                <span>→</span>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div> <!-- End Canvas Content -->

</main> <!-- End Studio Canvas -->
</div> <!-- End Laboratory Layout -->

<!-- Laboratory Interactions -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Menu Toggle
    const toggle = document.getElementById('laboratoryToggle');
    const rail = document.getElementById('identityRail');
    const overlay = document.getElementById('menuOverlay');

    if (toggle) {
        toggle.addEventListener('click', () => {
            rail.classList.toggle('open');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            rail.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // Flash message auto-dismiss
    const flash = document.getElementById('flashMessage');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 500);
        }, 5000);
    }
});
</script>

</body>
</html>

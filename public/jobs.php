<?php
/**
 * Neuromax – Jobs Page (Face Swap History)
 * 
 * View all face-swap jobs with source, target, result comparison.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../app/models/Job.php';
$jobModel = new Job();

$userId = currentUserId();
$jobs = $jobModel->findByUser($userId);

// If viewing a specific job result
$viewJob = null;
if (isset($_GET['view'])) {
    $viewJob = $jobModel->findById((int)$_GET['view']);
    if ($viewJob && (int)$viewJob['user_id'] !== $userId) {
        $viewJob = null;
    }
}

$pageTitle = 'My Jobs';
$pageDescription = 'View your face swap history';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>My Face Swap Jobs</h1>
    <p>Track the status of all your AI face swap jobs.</p>
</div>

<div class="page-content">

    <div class="flex-between mb-3">
        <div>
            <a href="<?= publicUrl('upload.php') ?>" class="btn btn-primary"><i data-lucide="refresh-cw" style="width:16px;"></i> New Face Swap</a>
        </div>
        <div class="text-sm text-muted">
            <?= count($jobs) ?> total job<?= count($jobs) !== 1 ? 's' : '' ?>
        </div>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><i data-lucide="inbox" style="width:48px;height:48px;"></i></span>
                <h3>No jobs yet</h3>
                <p>Upload your first face swap to get started!</p>
                <a href="<?= publicUrl('upload.php') ?>" class="btn btn-primary">Start Face Swap</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Source Face</th>
                            <th>Target Photo</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><strong>#<?= (int)$job['id'] ?></strong></td>
                            <td>
                                <img src="<?= assetUrl('uploads/' . e($job['source_path'])) ?>"
                                     alt="Source" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">
                            </td>
                            <td>
                                <img src="<?= assetUrl('uploads/' . e($job['file_path'])) ?>"
                                     alt="Target" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">
                            </td>
                            <td><span class="badge <?= statusBadgeClass($job['status']) ?>"><?= e(ucfirst($job['status'])) ?></span></td>
                            <td class="text-sm"><?= formatDateTime($job['created_at']) ?></td>
                            <td>
                                <?php if ($job['status'] === 'completed' && $job['result_path']): ?>
                                    <button class="btn btn-sm btn-outline" onclick="openResult(<?= (int)$job['id'] ?>, '<?= e($job['source_path']) ?>', '<?= e($job['file_path']) ?>', '<?= e($job['result_path']) ?>')">
                                        <i data-lucide="eye" style="width:16px;"></i> View
                                    </button>
                                    <a href="<?= assetUrl('results/' . e($job['result_path'])) ?>" download class="btn btn-sm btn-secondary">
                                        <i data-lucide="download" style="width:16px;"></i>
                                    </a>
                                <?php elseif ($job['status'] === 'failed'): ?>
                                    <span class="text-sm" style="color: var(--accent-red);" title="<?= e($job['error_msg'] ?? 'Unknown error') ?>">
                                        <i data-lucide="alert-triangle" style="width:14px;vertical-align:middle;"></i> <?= e(truncate($job['error_msg'] ?? 'Error', 30)) ?>
                                    </span>
                                <?php elseif ($job['status'] === 'processing'): ?>
                                    <span class="spinner spinner-sm"></span>
                                <?php else: ?>
                                    <span class="text-muted text-sm">Queued</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Result Modal -->
<div class="modal-overlay" id="resultModal">
    <div class="modal" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Face Swap Result</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <!-- 3-column comparison: source, target, result -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <div class="label" style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; font-weight: 600;">Source Face</div>
                <img src="" id="modalSource" alt="Source" style="width: 100%; border-radius: 10px; border: 1px solid var(--border-color);">
            </div>
            <div>
                <div class="label" style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; font-weight: 600;">Target Photo</div>
                <img src="" id="modalTarget" alt="Target" style="width: 100%; border-radius: 10px; border: 1px solid var(--border-color);">
            </div>
            <div>
                <div class="label" style="font-size: 12px; color: var(--accent-green); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; font-weight: 600;"><i data-lucide="sparkles" style="width:14px;vertical-align:middle;"></i> Result</div>
                <img src="" id="modalResult" alt="Result" style="width: 100%; border-radius: 10px; border: 2px solid var(--accent-green);">
            </div>
        </div>
        <div class="text-center mt-2">
            <a href="" id="modalDownload" download class="btn btn-primary"><i data-lucide="download" style="width:16px;"></i> Download Result</a>
        </div>
    </div>
</div>

<script>
function openResult(jobId, sourcePath, targetPath, resultPath) {
    const baseUrl = '<?= ASSETS_URL ?>';
    document.getElementById('modalSource').src = baseUrl + '/uploads/' + sourcePath;
    document.getElementById('modalTarget').src = baseUrl + '/uploads/' + targetPath;
    document.getElementById('modalResult').src = baseUrl + '/results/' + resultPath;
    document.getElementById('modalDownload').href = baseUrl + '/results/' + resultPath;
    document.getElementById('resultModal').classList.add('show');
}

function closeModal() {
    document.getElementById('resultModal').classList.remove('show');
}

document.getElementById('resultModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

<?php if ($viewJob && $viewJob['status'] === 'completed' && $viewJob['result_path']): ?>
openResult(<?= (int)$viewJob['id'] ?>, '<?= e($viewJob['source_path']) ?>', '<?= e($viewJob['file_path']) ?>', '<?= e($viewJob['result_path']) ?>');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

<?php
/**
 * Neuromax – Upload Page (Face Swap)
 * 
 * Users upload TWO images:
 *   1. Source Face — the face to swap IN (your face / the replacement)
 *   2. Target Photo — the photo where the face gets REPLACED
 * 
 * Result: Target photo with the source face swapped onto it.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/JobController.php';
    $controller = new JobController();
    $controller->create();
    exit;
}

require_once __DIR__ . '/../app/models/Subscription.php';
require_once __DIR__ . '/../app/models/Job.php';

$subModel = new Subscription();
$jobModel = new Job();

$userId = currentUserId();
$userSub = $subModel->getUserSubscription($userId);
$planId = $userSub ? (int)$userSub['id'] : 1;
$canUseHQ = ($planId >= 2);

$maxJobs = $subModel->getUserMaxJobs($userId);
$monthlyJobs = $jobModel->countMonthlyJobs($userId);
$remaining = max(0, $maxJobs - $monthlyJobs);

$pageTitle = 'Face Swap';
$pageDescription = 'Upload images for AI face swap';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1><i data-lucide="refresh-cw" style="display:inline-block; vertical-align:middle; width:32px; height:32px;"></i> AI Face Swap</h1>
    <p>Swap a face from one photo onto another using artificial intelligence.</p>
</div>

<div class="page-content">

    <!-- Plan Limit Info -->
    <div class="alert alert-info mb-3">
        <span>You have <strong><?= $remaining ?></strong> of <strong><?= $maxJobs >= 9999 ? '∞' : $maxJobs ?></strong> face swaps remaining this month.
        <?php if ($remaining === 0): ?>
            <a href="<?= publicUrl('plans.php') ?>" style="margin-left: 8px;">Upgrade your plan →</a>
        <?php endif; ?>
        </span>
    </div>

    <?php if ($remaining > 0): ?>

    <!-- How It Works -->
    <div class="card mb-3" style="padding: 20px;">
        <div class="flex gap-2" style="justify-content: center; text-align: center;">
            <div style="flex: 1;">
                <div style="font-size: 32px; margin-bottom: 8px;"></div>
                <strong>Step 1</strong>
                <p class="text-sm text-muted">Upload Source Face</p>
                <p class="text-sm text-muted">(the face to swap IN)</p>
            </div>
            <div style="font-size: 28px; color: var(--accent-blue); align-self: center;">→</div>
            <div style="flex: 1;">
                <div style="font-size: 32px; margin-bottom: 8px;"></div>
                <strong>Step 2</strong>
                <p class="text-sm text-muted">Upload Target Photo</p>
                <p class="text-sm text-muted">(face gets REPLACED here)</p>
            </div>
            <div style="font-size: 28px; color: var(--accent-blue); align-self: center;">→</div>
            <div style="flex: 1;">
                <div style="font-size: 32px; margin-bottom: 8px;"></div>
                <strong>Step 3</strong>
                <p class="text-sm text-muted">AI swaps the faces</p>
                <p class="text-sm text-muted">(deepfake magic)</p>
            </div>
        </div>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
        <?= csrfField() ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

            <!-- SOURCE FACE -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i data-lucide="user" style="display:inline-block; vertical-align:middle; width:20px;"></i> Source Face</h3>
                </div>
                <p class="text-sm text-muted mb-3">Upload the face you want to swap <strong>onto</strong> the target photo. This should be a clear, front-facing photo.</p>

                <!-- Tab switcher -->
                <div class="flex gap-1 mb-3">
                    <button type="button" class="btn btn-primary btn-sm tab-btn active" data-tab="source-file" data-group="source">
                        <i data-lucide="folder" style="width:16px;"></i> File
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm tab-btn" data-tab="source-webcam" data-group="source">
                        <i data-lucide="camera" style="width:16px;"></i> Webcam
                    </button>
                </div>

                <!-- File Upload -->
                <div id="source-file" class="tab-content active">
                    <div class="upload-zone" id="sourceUploadZone">
                        <span class="upload-icon"></span>
                        <div class="upload-text"><i data-lucide="image" style="width:16px;"></i> Drop source face here</div>
                        <div class="upload-subtext">Clear, front-facing photo · JPG, PNG · Max 5MB</div>
                        <input type="file" name="source_image" id="sourceFileInput" accept="image/jpeg,image/png">
                    </div>
                    <div class="upload-preview" id="sourcePreview">
                        <img src="" alt="Source Preview" class="preview-image" id="sourcePreviewImg">
                        <p class="text-sm text-muted text-center mt-1" id="sourceFileInfo"></p>
                    </div>
                </div>

                <!-- Webcam -->
                <div id="source-webcam" class="tab-content" style="display: none;">
                    <div class="webcam-container">
                        <video id="sourceVideo" autoplay muted playsinline></video>
                        <canvas id="sourceCanvas"></canvas>
                    </div>
                    <div class="webcam-controls mt-2">
                        <button type="button" class="btn btn-primary btn-sm webcam-start" data-target="source"><i data-lucide="camera" style="width:16px;"></i> Start</button>
                        <button type="button" class="btn btn-success btn-sm webcam-capture" data-target="source" style="display:none;"><i data-lucide="camera" style="width:16px;"></i> Capture</button>
                        <button type="button" class="btn btn-secondary btn-sm webcam-retake" data-target="source" style="display:none;"><i data-lucide="refresh-cw" style="width:16px;"></i> Retake</button>
                    </div>
                    <input type="hidden" name="source_webcam" id="sourceWebcamData">
                </div>
            </div>

            <!-- TARGET PHOTO -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i data-lucide="image" style="display:inline-block; vertical-align:middle; width:20px;"></i> Target Photo</h3>
                </div>
                <p class="text-sm text-muted mb-3">Upload the photo where the face should be <strong>replaced</strong>. The AI will detect the face here and swap it.</p>

                <!-- Tab switcher -->
                <div class="flex gap-1 mb-3">
                    <button type="button" class="btn btn-primary btn-sm tab-btn active" data-tab="target-file" data-group="target">
                        <i data-lucide="folder" style="width:16px;"></i> File
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm tab-btn" data-tab="target-webcam" data-group="target">
                        <i data-lucide="camera" style="width:16px;"></i> Webcam
                    </button>
                </div>

                <!-- File Upload -->
                <div id="target-file" class="tab-content active">
                    <div class="upload-zone" id="targetUploadZone">
                        <span class="upload-icon"><i data-lucide="image" style="width:48px;height:48px;"></i></span>
                        <div class="upload-text">Drop target photo here</div>
                        <div class="upload-subtext">Photo with face to replace · JPG, PNG · Max 5MB</div>
                        <input type="file" name="target_image" id="targetFileInput" accept="image/jpeg,image/png">
                    </div>
                    <div class="upload-preview" id="targetPreview">
                        <img src="" alt="Target Preview" class="preview-image" id="targetPreviewImg">
                        <p class="text-sm text-muted text-center mt-1" id="targetFileInfo"></p>
                    </div>
                </div>

                <!-- Webcam -->
                <div id="target-webcam" class="tab-content" style="display: none;">
                    <div class="webcam-container">
                        <video id="targetVideo" autoplay muted playsinline></video>
                        <canvas id="targetCanvas"></canvas>
                    </div>
                    <div class="webcam-controls mt-2">
                        <button type="button" class="btn btn-primary btn-sm webcam-start" data-target="target"><i data-lucide="camera" style="width:16px;"></i> Start</button>
                        <button type="button" class="btn btn-success btn-sm webcam-capture" data-target="target" style="display:none;"><i data-lucide="camera" style="width:16px;"></i> Capture</button>
                        <button type="button" class="btn btn-secondary btn-sm webcam-retake" data-target="target" style="display:none;"><i data-lucide="refresh-cw" style="width:16px;"></i> Retake</button>
                    </div>
                    <input type="hidden" name="target_webcam" id="targetWebcamData">
                </div>
            </div>

        </div>

        <!-- Model Quality Selection -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title"><i data-lucide="settings" style="display:inline-block; vertical-align:middle; width:20px;"></i> Processing Quality</h3>
            </div>
            <p class="text-sm text-muted mb-3">Select the AI engine used to process your image.</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <label style="border: 2px solid var(--accent-blue); padding: 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: border-color 0.2s;" onchange="this.style.borderColor='var(--accent-blue)'; this.nextElementSibling.style.borderColor='var(--border-color)';">
                    <input type="radio" name="model_tier" value="standard" checked style="width: 20px; height: 20px; accent-color: var(--accent-blue);">
                    <div>
                        <strong style="display: block; font-size: 16px; margin-bottom: 4px;">Standard Engine <i data-lucide="zap" style="width:16px;vertical-align:middle;"></i></strong>
                        <span class="text-sm text-muted">A fast, matrix-based face swap. Processing takes ~5 seconds.</span>
                    </div>
                </label>
                
                <label style="border: 2px solid var(--border-color); padding: 16px; border-radius: 8px; <?= $canUseHQ ? 'cursor: pointer;' : 'cursor: not-allowed; opacity: 0.5;' ?> display: flex; align-items: center; gap: 12px; transition: border-color 0.2s;" <?= $canUseHQ ? 'onchange="this.style.borderColor=\'var(--accent-blue)\'; this.previousElementSibling.style.borderColor=\'var(--border-color)\';"' : '' ?>>
                    <input type="radio" name="model_tier" value="hq" <?= $canUseHQ ? '' : 'disabled' ?> style="width: 20px; height: 20px; accent-color: var(--accent-blue);">
                    <div>
                        <strong style="display: block; font-size: 16px; margin-bottom: 4px;">HQ Generative Engine <i data-lucide="sparkles" style="width:16px;vertical-align:middle;"></i></strong>
                        <span class="text-sm text-muted">
                            <?= $canUseHQ ? 'Dual-mode photorealistic 1:1 Inpaint execution. Takes longer.' : 'Generative swapping is exclusively available on Pro & Ultra plans.' ?>
                        </span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="card mt-3" style="text-align: center; padding: 32px;">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" style="min-width: 280px;">
                <i data-lucide="refresh-cw" style="width:16px;"></i> Start Face Swap
            </button>
            <div class="progress-bar mt-2" id="progressBar" style="display: none; max-width: 400px; margin: 12px auto 0;">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p class="text-sm text-muted mt-2" id="statusText">Upload both images, then click to start the AI face swap.</p>
        </div>
    </form>

    <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><i data-lucide="ban" style="width:48px;height:48px;"></i></span>
                <h3>Monthly Limit Reached</h3>
                <p>You've used all your face swaps this month. Upgrade your plan for more!</p>
                <a href="<?= publicUrl('plans.php') ?>" class="btn btn-primary">View Plans</a>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$extraScripts = [assetUrl('js/upload.js')];
require_once __DIR__ . '/../templates/footer.php';
?>

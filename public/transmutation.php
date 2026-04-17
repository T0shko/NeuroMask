<?php
/**
 * NeuroMask – Transmutation Page
 * Concept: The Chamber of Identity Transformation
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

require_once __DIR__ . '/../templates/dashboard-new.php';
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
?>

<!-- Canvas Header -->
<div class="canvas-header">
    <h1>The Transmutation Chamber</h1>
</div>

<!-- Canvas Content -->
<div class="canvas-content">

    <!-- Chamber Capacity Alert -->
    <?php if ($remaining === 0): ?>
    <div class="alert alert-error mb-3" style="margin-bottom: 40px;">
        <span>Monthly capacity reached. <a href="<?= publicUrl('plans.php') ?>">Elevate access →</a></span>
    </div>
    <?php else: ?>
    <div class="alert alert-info mb-3" style="margin-bottom: 40px;">
        <span>You have <strong><?= $remaining ?></strong> of <strong><?= $maxJobs >= 9999 ? '∞' : $maxJobs ?></strong> transmutations available this cycle.</span>
    </div>
    <?php endif; ?>

    <!-- Process Overview -->
    <div class="exhibit-card" style="padding: 32px; margin-bottom: 32px;">
        <div class="flex gap-2" style="display: flex; gap: 24px; justify-content: center; text-align: center;">
            <div style="flex: 1;">
                <div style="font-size: 28px; margin-bottom: 12px;">◐</div>
                <strong>Phase I</strong>
                <p class="text-sm text-muted">Provide Source Identity</p>
                <p class="text-sm text-muted">(specimen to transmute)</p>
            </div>
            <div style="font-size: 24px; color: var(--identity-cyan); align-self: center;">↝</div>
            <div style="flex: 1;">
                <div style="font-size: 28px; margin-bottom: 12px;">◑</div>
                <strong>Phase II</strong>
                <p class="text-sm text-muted">Provide Target Canvas</p>
                <p class="text-sm text-muted">(where transmutation occurs)</p>
            </div>
            <div style="font-size: 24px; color: var(--identity-cyan); align-self: center;">↝</div>
            <div style="flex: 1;">
                <div style="font-size: 28px; margin-bottom: 12px;">◈</div>
                <strong>Phase III</strong>
                <p class="text-sm text-muted">Neural Processing</p>
                <p class="text-sm text-muted">(identity is reshaped)</p>
            </div>
        </div>
    </div>

    <!-- Transmutation Form -->
    <form method="POST" action="" enctype="multipart/form-data" id="transmutationForm">
        <?= csrfField() ?>

        <div class="transmutation-chamber">

            <!-- Source Specimen Station -->
            <div class="chamber-station">
                <div class="station-header">
                    <h2 class="station-title">Source Specimen</h2>
                    <span class="station-icon">◐</span>
                </div>

                <!-- Tab Controls -->
                <div class="flex gap-1 mb-3" style="display: flex; gap: 12px; margin-bottom: 20px;">
                    <button type="button" class="btn-identity btn-ghost-identity btn-sm tab-btn active" data-tab="source-file" data-group="source">
                        Archive
                    </button>
                    <button type="button" class="btn-identity btn-ghost-identity btn-sm tab-btn" data-tab="source-webcam" data-group="source">
                        Capture
                    </button>
                </div>

                <!-- File Upload -->
                <div id="source-file" class="tab-content active">
                    <div class="specimen-pod" id="sourcePod">
                        <div class="specimen-placeholder" id="sourcePlaceholder">
                            <span class="specimen-placeholder-icon">◐</span>
                            <div class="specimen-placeholder-text">Source Identity</div>
                            <div class="specimen-placeholder-subtext">Clear specimen · JPG, PNG · Max 5MB</div>
                        </div>
                        <img id="sourcePreviewImg" alt="Source" style="display: none;">
                        <input type="file" name="source_image" id="sourceFileInput" accept="image/jpeg,image/png">
                    </div>
                </div>

                <!-- Webcam Capture -->
                <div id="source-webcam" class="tab-content" style="display: none;">
                    <div class="specimen-pod" id="sourceWebcamPod">
                        <div class="specimen-placeholder" id="sourceWebcamPlaceholder">
                            <span class="specimen-placeholder-icon">◐</span>
                            <div class="specimen-placeholder-text">Ready to Capture</div>
                        </div>
                        <video id="sourceVideo" autoplay muted playsinline style="display: none; width: 100%; height: 100%; object-fit: contain;"></video>
                        <canvas id="sourceCanvas" style="display: none;"></canvas>
                        <img id="sourceWebcamPreview" alt="Captured" style="display: none;">
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; margin-top: 16px;">
                        <button type="button" class="btn-identity btn-ghost-identity webcam-start" data-target="source">
                            Initialize
                        </button>
                        <button type="button" class="btn-identity btn-primary-identity webcam-capture" data-target="source" style="display: none;">
                            Capture
                        </button>
                        <button type="button" class="btn-identity btn-ghost-identity webcam-retake" data-target="source" style="display: none;">
                            Retake
                        </button>
                    </div>
                    <input type="hidden" name="source_webcam" id="sourceWebcamData">
                </div>
            </div>

            <!-- Target Canvas Station -->
            <div class="chamber-station">
                <div class="station-header">
                    <h2 class="station-title">Target Canvas</h2>
                    <span class="station-icon">◑</span>
                </div>

                <!-- Tab Controls -->
                <div class="flex gap-1 mb-3" style="display: flex; gap: 12px; margin-bottom: 20px;">
                    <button type="button" class="btn-identity btn-ghost-identity btn-sm tab-btn active" data-tab="target-file" data-group="target">
                        Archive
                    </button>
                    <button type="button" class="btn-identity btn-ghost-identity btn-sm tab-btn" data-tab="target-webcam" data-group="target">
                        Capture
                    </button>
                </div>

                <!-- File Upload -->
                <div id="target-file" class="tab-content active">
                    <div class="specimen-pod" id="targetPod">
                        <div class="specimen-placeholder" id="targetPlaceholder">
                            <span class="specimen-placeholder-icon">◑</span>
                            <div class="specimen-placeholder-text">Target Canvas</div>
                            <div class="specimen-placeholder-subtext">Photo with identity · JPG, PNG · Max 5MB</div>
                        </div>
                        <img id="targetPreviewImg" alt="Target" style="display: none;">
                        <input type="file" name="target_image" id="targetFileInput" accept="image/jpeg,image/png">
                    </div>
                </div>

                <!-- Webcam Capture -->
                <div id="target-webcam" class="tab-content" style="display: none;">
                    <div class="specimen-pod" id="targetWebcamPod">
                        <div class="specimen-placeholder" id="targetWebcamPlaceholder">
                            <span class="specimen-placeholder-icon">◑</span>
                            <div class="specimen-placeholder-text">Ready to Capture</div>
                        </div>
                        <video id="targetVideo" autoplay muted playsinline style="display: none; width: 100%; height: 100%; object-fit: contain;"></video>
                        <canvas id="targetCanvas" style="display: none;"></canvas>
                        <img id="targetWebcamPreview" alt="Captured" style="display: none;">
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; margin-top: 16px;">
                        <button type="button" class="btn-identity btn-ghost-identity webcam-start" data-target="target">
                            Initialize
                        </button>
                        <button type="button" class="btn-identity btn-primary-identity webcam-capture" data-target="target" style="display: none;">
                            Capture
                        </button>
                        <button type="button" class="btn-identity btn-ghost-identity webcam-retake" data-target="target" style="display: none;">
                            Retake
                        </button>
                    </div>
                    <input type="hidden" name="target_webcam" id="targetWebcamData">
                </div>
            </div>

        </div>

        <!-- Processing Mode Selector -->
        <div class="mode-selector">
            <div class="mode-header">
                <h2 class="mode-title">Processing Mode</h2>
                <p class="mode-subtitle">Select the neural engine for transmutation</p>
            </div>

            <div class="modes-grid">
                <div class="mode-option">
                    <input type="radio" name="model_tier" value="standard" id="modestandard" checked>
                    <label for="modestandard">
                        <div class="mode-badge">● Fast</div>
                        <div class="mode-name">Matrix Engine</div>
                        <div class="mode-description">
                            Direct neural face transposition. Processing completes in approximately 5 seconds.
                        </div>
                        <div class="mode-meta">
                            <span class="meta-item"><span class="meta-symbol">◈</span> Speed: ~5s</span>
                        </div>
                    </label>
                </div>

                <div class="mode-option" style="<?= $canUseHQ ? '' : 'opacity: 0.5; pointer-events: none;' ?>">
                    <input type="radio" name="model_tier" value="hq" id="modehq" <?= $canUseHQ ? '' : 'disabled' ?>>
                    <label for="modehq">
                        <div class="mode-badge">◈ HD</div>
                        <div class="mode-name">Generative Engine</div>
                        <div class="mode-description">
                            Dual-mode photorealistic enhancement with GFPGAN and Real-ESRGAN. Takes longer but results are sharper.
                        </div>
                        <div class="mode-meta">
                            <span class="meta-item"><span class="meta-symbol">◉</span> Speed: ~30s</span>
                            <?php if (!$canUseHQ): ?>
                            <span class="meta-item" style="color: var(--identity-rose);"><span class="meta-symbol">◆</span> Pro & Ultra only</span>
                            <?php endif; ?>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Chamber Actions -->
        <?php if ($remaining > 0): ?>
        <div class="chamber-actions">
            <button type="submit" class="btn-identity btn-primary-identity btn-lg" id="initiateTransmutation" style="min-width: 320px;">
                <span>Initiate Transmutation</span>
                <span>↝</span>
            </button>
        </div>

        <!-- Processing State -->
        <div class="chamber-processing" id="chamberProcessing">
            <div class="processing-animation">
                <div class="processing-ring"></div>
                <div class="processing-ring"></div>
                <div class="processing-ring"></div>
            </div>
            <div class="processing-status">Transmuting Identity</div>
            <div class="processing-message" id="processingMessage">Neural pathways initializing...</div>
            <div class="processing-progress">
                <div class="processing-progress-fill" id="processingProgressFill"></div>
            </div>
        </div>
        <?php else: ?>
        <div class="exhibit-card" style="text-align: center; padding: 60px 40px;">
            <p class="gallery-empty-text">Monthly Capacity Reached</p>
            <p class="gallery-empty-subtext" style="margin: 16px 0 24px;">Elevate your access to continue transmutation</p>
            <a href="<?= publicUrl('plans.php') ?>" class="btn-identity btn-primary-identity">
                <span>Elevate Access</span>
                <span>→</span>
            </a>
        </div>
        <?php endif; ?>

    </form>

</div> <!-- End Canvas Content -->

</main> <!-- End Studio Canvas -->
</div> <!-- End Laboratory Layout -->

<!-- Camera Capture Modal -->
<div class="camera-capture" id="cameraCapture">
    <div class="camera-frame">
        <div class="camera-viewport">
            <video id="captureVideo" autoplay muted playsinline></video>
            <div class="camera-overlay"></div>
        </div>
        <div class="flex gap-2" style="display: flex; gap: 16px; justify-content: center;">
            <button type="button" class="btn-identity btn-ghost-identity" onclick="closeCameraCapture()">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Transmutation Interactions -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Menu Toggle (from dashboard)
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

    // Tab Switching for Upload Methods
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const group = this.dataset.group;
            const target = this.dataset.tab;

            // Update buttons in this group
            document.querySelectorAll(`[data-group="${group}"].tab-btn`).forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            // Update content panels
            document.querySelectorAll(`[id^="${group}-"].tab-content`).forEach(p => {
                p.style.display = 'none';
            });
            document.getElementById(target).style.display = 'block';
        });
    });

    // File Upload Preview
    function setupFileUpload(inputId, podId, placeholderId) {
        const input = document.getElementById(inputId);
        const pod = document.getElementById(podId);
        const placeholder = document.getElementById(placeholderId);

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = pod.querySelector('img');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    placeholder.style.display = 'none';
                    pod.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    setupFileUpload('sourceFileInput', 'sourcePod', 'sourcePlaceholder');
    setupFileUpload('targetFileInput', 'targetPod', 'targetPlaceholder');

    // Webcam Handling
    let currentStream = null;

    async function startWebcam(target) {
        try {
            currentStream = await navigator.mediaDevices.getUserMedia({ video: true });
            const video = document.getElementById(`${target}Video`);
            const placeholder = document.getElementById(`${target}WebcamPlaceholder`);
            const pod = document.getElementById(`${target}WebcamPod`);

            video.srcObject = currentStream;
            video.style.display = 'block';
            placeholder.style.display = 'none';

            // Update buttons
            document.querySelector(`.webcam-start[data-target="${target}"]`).style.display = 'none';
            document.querySelector(`.webcam-capture[data-target="${target}"]`).style.display = 'inline-flex';
            document.querySelector(`.webcam-retake[data-target="${target}"]`).style.display = 'none';

            return true;
        } catch (error) {
            console.error('Camera error:', error);
            alert('Unable to access camera. Please grant camera permissions.');
            return false;
        }
    }

    async function captureWebcam(target) {
        const video = document.getElementById(`${target}Video`);
        const canvas = document.getElementById(`${target}Canvas`);
        const preview = document.getElementById(`${target}WebcamPreview`);
        const videoEl = document.getElementById(`${target}Video`);

        if (!video.srcObject) return;

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/png');
        preview.src = imageData;
        preview.style.display = 'block';
        video.style.display = 'none';

        // Store data
        document.getElementById(`${target}WebcamData`).value = imageData;

        // Update buttons
        document.querySelector(`.webcam-start[data-target="${target}"]`).style.display = 'none';
        document.querySelector(`.webcam-capture[data-target="${target}"]`).style.display = 'none';
        document.querySelector(`.webcam-retake[data-target="${target}"]`).style.display = 'inline-flex';
    }

    async function retakeWebcam(target) {
        const preview = document.getElementById(`${target}WebcamPreview`);
        const video = document.getElementById(`${target}Video`);

        preview.style.display = 'none';
        video.style.display = 'block';

        // Update buttons
        document.querySelector(`.webcam-start[data-target="${target}"]`).style.display = 'none';
        document.querySelector(`.webcam-capture[data-target="${target}"]`).style.display = 'inline-flex';
        document.querySelector(`.webcam-retake[data-target="${target}"]`).style.display = 'none';
    }

    // Webcam button handlers
    document.querySelectorAll('.webcam-start').forEach(btn => {
        btn.addEventListener('click', () => startWebcam(btn.dataset.target));
    });

    document.querySelectorAll('.webcam-capture').forEach(btn => {
        btn.addEventListener('click', () => captureWebcam(btn.dataset.target));
    });

    document.querySelectorAll('.webcam-retake').forEach(btn => {
        btn.addEventListener('click', () => retakeWebcam(btn.dataset.target));
    });

    // Form submission with processing animation
    const form = document.getElementById('transmutationForm');
    const processing = document.getElementById('chamberProcessing');
    const progressFill = document.getElementById('processingProgressFill');
    const processingMessage = document.getElementById('processingMessage');

    const messages = [
        'Neural pathways initializing...',
        'Detecting identity specimens...',
        'Analyzing facial features...',
        'Initiating transmutation protocol...',
        'Processing through neural layers...',
        'Finalizing transformation...',
        'Transmutation complete.'
    ];

    form.addEventListener('submit', function(e) {
        // Basic validation
        const sourceFile = document.getElementById('sourceFileInput').files[0];
        const targetFile = document.getElementById('targetFileInput').files[0];
        const sourceWebcam = document.getElementById('sourceWebcamData').value;
        const targetWebcam = document.getElementById('targetWebcamData').value;

        if ((!sourceFile && !sourceWebcam) || (!targetFile && !targetWebcam)) {
            e.preventDefault();
            alert('Please provide both source identity and target canvas specimens.');
            return;
        }

        // Show processing animation
        processing.classList.add('show');

        let progress = 0;
        let messageIndex = 0;

        const progressInterval = setInterval(() => {
            progress += 3;
            if (progress >= 100) progress = 100;

            progressFill.style.width = progress + '%';

            if (progress % 15 === 0 && messageIndex < messages.length) {
                processingMessage.textContent = messages[messageIndex];
                messageIndex++;
            }
        }, 200);

        // Allow form to submit normally
    });
});

// Camera capture modal functions
function openCameraCapture() {
    document.getElementById('cameraCapture').classList.add('show');
}

function closeCameraCapture() {
    document.getElementById('cameraCapture').classList.remove('show');
}
</script>

<link rel="stylesheet" href="<?= assetUrl('css/transmutation.css') ?>">

</body>
</html>

<?php
/**
 * Neuromax – Job Controller
 * 
 * Handles face-swap job creation: source face upload + target photo upload.
 * Validates both images, creates job, triggers AI processing.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../services/FileService.php';
require_once __DIR__ . '/../services/AIService.php';

class JobController
{
    private Job $jobModel;
    private Subscription $subModel;
    private FileService $fileService;
    private AIService $aiService;

    public function __construct()
    {
        $this->jobModel = new Job();
        $this->subModel = new Subscription();
        $this->fileService = new FileService();
        $this->aiService = new AIService();
    }

    /**
     * Handle new face-swap job creation.
     * Requires two images: source face + target photo.
     */
    public function create(): void
    {
        requireLogin();
        requireCsrf();

        $userId = currentUserId();

        // Check plan limits
        $maxJobs = $this->subModel->getUserMaxJobs($userId);
        $monthlyJobs = $this->jobModel->countMonthlyJobs($userId);

        if ($monthlyJobs >= $maxJobs) {
            setFlash('error', 'Monthly job limit reached (' . $maxJobs . '). Please upgrade your plan.');
            redirect(publicUrl('upload.php'));
            return;
        }

        // ── Upload SOURCE face image ──
        $sourceResult = null;
        $sourceWebcam = $_POST['source_webcam'] ?? '';

        if (!empty($sourceWebcam)) {
            $sourceResult = $this->fileService->handleBase64Upload($sourceWebcam);
        } elseif (isset($_FILES['source_image']) && $_FILES['source_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $sourceResult = $this->fileService->handleUpload($_FILES['source_image']);
        } else {
            setFlash('error', 'Please provide a source face image (the face you want to swap in).');
            redirect(publicUrl('upload.php'));
            return;
        }

        if (!$sourceResult['success']) {
            setFlash('error', 'Source image: ' . $sourceResult['error']);
            redirect(publicUrl('upload.php'));
            return;
        }

        // ── Upload TARGET photo ──
        $targetResult = null;
        $targetWebcam = $_POST['target_webcam'] ?? '';

        if (!empty($targetWebcam)) {
            $targetResult = $this->fileService->handleBase64Upload($targetWebcam);
        } elseif (isset($_FILES['target_image']) && $_FILES['target_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $targetResult = $this->fileService->handleUpload($_FILES['target_image']);
        } else {
            setFlash('error', 'Please provide a target photo (the photo where the face will be replaced).');
            redirect(publicUrl('upload.php'));
            return;
        }

        if (!$targetResult['success']) {
            setFlash('error', 'Target image: ' . $targetResult['error']);
            redirect(publicUrl('upload.php'));
            return;
        }

        // ── Create job record ──
        $jobId = $this->jobModel->create([
            'user_id'     => $userId,
            'source_path' => $sourceResult['filename'],
            'file_path'   => $targetResult['filename'],
            'effect'      => 'faceswap',
        ]);

        // ── Trigger AI processing ──
        $this->aiService->processJob($jobId);

        $job = $this->jobModel->findById($jobId);
        if ($job && $job['status'] === 'completed') {
            setFlash('success', 'Face swap completed! Job #' . $jobId);
        } elseif ($job && $job['status'] === 'failed') {
            setFlash('error', 'Face swap failed: ' . ($job['error_msg'] ?? 'Unknown error. Make sure both images contain clear faces.'));
        } else {
            setFlash('info', 'Job #' . $jobId . ' is being processed...');
        }

        redirect(publicUrl('jobs.php'));
    }
}

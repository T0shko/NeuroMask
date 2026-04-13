<?php
/**
 * Neuromax – AI Service
 * 
 * Manages the execution of the Python face-swap AI script.
 * Passes source face and target photo to the script.
 * Handles job status transitions and error capture.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../models/Job.php';

class AIService
{
    private Job $jobModel;

    public function __construct()
    {
        $this->jobModel = new Job();
    }

    /**
     * Process a face-swap job by executing the Python AI script.
     *
     * The Python script receives:
     *   - source_path: the face image to use as replacement
     *   - target_path: the photo where the face gets swapped
     *   - output_path: where to save the result
     *
     * @param int $jobId  The job ID to process
     * @return bool       True if processing completed successfully
     */
    public function processJob(int $jobId): bool
    {
        $job = $this->jobModel->findById($jobId);
        if (!$job) {
            return false;
        }

        // Update status to processing
        $this->jobModel->updateStatus($jobId, 'processing');

        // Build file paths
        $sourcePath = UPLOAD_DIR . basename($job['source_path']);
        $targetPath = UPLOAD_DIR . basename($job['file_path']);
        $outputName = 'result_' . uniqid('', true) . '.jpg';
        $outputPath = RESULT_DIR . $outputName;

        // Ensure results directory exists
        if (!is_dir(RESULT_DIR)) {
            mkdir(RESULT_DIR, 0755, true);
        }

        // Verify input files exist
        if (!file_exists($sourcePath)) {
            $this->jobModel->updateStatus($jobId, 'failed', null, 'Source face image not found.');
            return false;
        }

        if (!file_exists($targetPath)) {
            $this->jobModel->updateStatus($jobId, 'failed', null, 'Target photo not found.');
            return false;
        }

        // Build the command with escaped arguments for security
        // Script signature: python process.py <source_path> <target_path> <output_path>
        $pythonPath = escapeshellarg(PYTHON_PATH);
        $scriptPath = escapeshellarg(AI_SCRIPT);
        $sourceArg  = escapeshellarg($sourcePath);
        $targetArg  = escapeshellarg($targetPath);
        $outputArg  = escapeshellarg($outputPath);

        $command = sprintf(
            '%s %s %s %s %s 2>&1',
            $pythonPath,
            $scriptPath,
            $sourceArg,
            $targetArg,
            $outputArg
        );

        // Execute the Python script
        $output = [];
        $returnCode = -1;
        exec($command, $output, $returnCode);

        $outputText = implode("\n", $output);

        // Check result
        if ($returnCode === 0 && file_exists($outputPath)) {
            // Success!
            $this->jobModel->updateStatus($jobId, 'completed', $outputName);
            return true;
        } else {
            // Failed
            $errorMsg = $outputText ?: 'AI processing failed with exit code ' . $returnCode;
            $this->jobModel->updateStatus($jobId, 'failed', null, $errorMsg);
            return false;
        }
    }
}

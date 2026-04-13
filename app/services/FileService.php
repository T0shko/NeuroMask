<?php
/**
 * Neuromax – File Service
 * 
 * Handles file upload validation, storage, and cleanup.
 * Validates MIME type, extension, and file size.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

class FileService
{
    /**
     * Validate and store an uploaded file.
     *
     * @param array $file  The $_FILES array entry
     * @return array       ['success' => bool, 'filename' => string|null, 'error' => string|null]
     */
    public function handleUpload(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->fail('Upload failed. Error code: ' . $file['error']);
        }

        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return $this->fail('File too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.');
        }

        if ($file['size'] === 0) {
            return $this->fail('File is empty.');
        }

        // Validate extension
        $extension = getFileExtension($file['name']);
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return $this->fail('Invalid file type. Only JPG and PNG are allowed.');
        }

        // Validate MIME type (more secure than extension check)
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, ALLOWED_TYPES)) {
            return $this->fail('Invalid file content. Only JPEG and PNG images are allowed.');
        }

        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return $this->fail('File is not a valid image.');
        }

        // Generate unique filename
        $filename = generateUniqueFilename($file['name']);

        // Ensure upload directory exists
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $destination = UPLOAD_DIR . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return $this->fail('Failed to save uploaded file.');
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'error'    => null,
        ];
    }

    /**
     * Handle a base64-encoded image (from webcam capture).
     *
     * @param string $base64Data  Raw base64 image data (with or without data URI prefix)
     * @return array
     */
    public function handleBase64Upload(string $base64Data): array
    {
        // Strip data URI prefix if present
        if (strpos($base64Data, 'data:image') === 0) {
            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
        }

        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return $this->fail('Invalid image data.');
        }

        // Verify it's a valid image
        $tmpFile = tempnam(sys_get_temp_dir(), 'nm_');
        file_put_contents($tmpFile, $imageData);

        $imageInfo = getimagesize($tmpFile);
        if ($imageInfo === false) {
            unlink($tmpFile);
            return $this->fail('Invalid image data.');
        }

        // Check MIME type
        $mimeType = $imageInfo['mime'];
        if (!in_array($mimeType, ALLOWED_TYPES)) {
            unlink($tmpFile);
            return $this->fail('Only JPEG and PNG images are supported.');
        }

        $ext = $mimeType === 'image/png' ? 'png' : 'jpg';
        $filename = uniqid('nm_webcam_', true) . '.' . $ext;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $destination = UPLOAD_DIR . $filename;
        rename($tmpFile, $destination);

        return [
            'success'  => true,
            'filename' => $filename,
            'error'    => null,
        ];
    }

    /**
     * Delete a file from uploads or results directory.
     */
    public function deleteFile(string $filename, string $directory = 'uploads'): bool
    {
        $dir = $directory === 'results' ? RESULT_DIR : UPLOAD_DIR;
        $path = $dir . basename($filename);

        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Return a failure response.
     */
    private function fail(string $message): array
    {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => $message,
        ];
    }
}

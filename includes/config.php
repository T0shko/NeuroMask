<?php
/**
 * Neuromax – Application Configuration
 * 
 * Central configuration file. All environment-specific
 * settings are defined here as constants.
 */

// ── Prevent direct access ──
if (!defined('NEUROMAX')) {
    define('NEUROMAX', true);
}

// ── Database Configuration ──
define('DB_HOST', 'localhost');
define('DB_NAME', 'neuromax');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default Laragon/XAMPP has no password
define('DB_CHARSET', 'utf8mb4');

// ── Application Paths ──
define('BASE_PATH', dirname(__DIR__));                          // c:\laragon\www\NeuroMask
define('BASE_URL', '/NeuroMask');                               // URL base path
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', BASE_URL . '/assets');

// ── File Upload Settings ──
define('UPLOAD_DIR', BASE_PATH . '/assets/uploads/');
define('RESULT_DIR', BASE_PATH . '/assets/results/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);                      // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// ── Python / AI Settings ──
define('PYTHON_PATH', 'E:\NeuroMaskAI\venv\Scripts\python.exe');
define('AI_SCRIPT', BASE_PATH . '/ai/process.py');

// ── Session Settings ──
define('SESSION_LIFETIME', 7200);                               // 2 hours

// ── App Meta ──
define('APP_NAME', 'Neuromax');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'AI Face Transformation Platform');

// ── Available AI Modes ──
define('AI_EFFECTS', [
    'faceswap' => 'Face Swap (DeepFake)',
]);

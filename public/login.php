<?php
/**
 * Neuromax – Login Page
 * 
 * User login form with email/password + face login option.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

redirectIfLoggedIn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    $controller = new AuthController();
    $controller->login();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – <?= APP_NAME ?></title>
    <meta name="description" content="Log in to your Neuromax account.">
    <link rel="stylesheet" href="<?= assetUrl('css/style.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'></text></svg>">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <div class="logo-icon"></div>
                <span class="logo-text"><?= APP_NAME ?></span>
            </div>
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Log in to your account</p>
        </div>

        <?php
        $flash = getFlash();
        if ($flash):
        ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <span><?= $flash['message'] ?></span>
            <button class="alert-dismiss" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com" required
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                🔓 Log In
            </button>
        </form>

        <!-- Face Login Option -->
        <div class="auth-divider">or</div>

        <div class="face-login-area" id="faceLoginArea">
            <span class="face-icon">🧑‍💻</span>
            <p>Log in with Face Recognition</p>
            <button type="button" class="btn btn-outline btn-block" id="startFaceLogin">
                 Real-time Face Login
            </button>
            <div id="faceLoginWebcam" style="display: none; margin-top: 16px;">
                <div class="webcam-container" style="position: relative;">
                    <video id="faceLoginVideo" autoplay muted playsinline style="width: 100%; border-radius: 8px;"></video>
                    <canvas id="faceLoginCanvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10;"></canvas>
                    <div id="scanOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 2px solid rgba(59,130,246,0.3); z-index: 5; pointer-events: none; border-radius: 8px; box-shadow: inset 0 0 20px rgba(59,130,246,0.15);"></div>
                </div>
                <p class="text-sm font-medium mt-3" id="faceLoginStatus" style="transition: color 0.3s; min-height: 20px; text-align: center;">Initializing scanner...</p>
                <div class="webcam-controls mt-3" style="display: flex; justify-content: center;">
                    <button type="button" class="btn btn-secondary btn-sm" id="cancelFaceLogin" style="padding: 6px 16px;">❌ Cancel Scanner</button>
                </div>
            </div>
        </div>

        <div class="auth-footer">
            Don't have an account? <a href="<?= publicUrl('register.php') ?>">Register</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script src="<?= assetUrl('js/face-login.js') ?>"></script>
</body>
</html>

<?php
/**
 * Neuromax – Registration Page
 * 
 * User registration form with validation.
 * Redirects to dashboard if already logged in.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to dashboard
redirectIfLoggedIn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    $controller = new AuthController();
    $controller->register();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – <?= APP_NAME ?></title>
    <meta name="description" content="Create your Neuromax account and start transforming faces with AI.">
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
            <h2>Create Account</h2>
            <p class="auth-subtitle">Join the future of face transformation</p>
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

        <form method="POST" action="" id="registerForm">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control"
                       placeholder="Enter your full name" required minlength="2" maxlength="100"
                       value="<?= e($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com" required
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min 8 characters" required minlength="8">
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                       placeholder="Repeat password" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                 Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="<?= publicUrl('login.php') ?>">Log In</a>
        </div>
    </div>
</div>
</body>
</html>

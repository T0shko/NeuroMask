<?php
/**
 * Neuromax – Header Template
 * 
 * Shared header for all authenticated dashboard pages.
 * Includes sidebar navigation, mobile hamburger, and flash messages.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Determine active page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> – <?= APP_NAME ?></title>
    <meta name="description" content="<?= e($pageDescription ?? APP_TAGLINE) ?>">
    <link rel="stylesheet" href="<?= assetUrl('css/style.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔷</text></svg>">
</head>
<body>
<div class="app-layout">

    <!-- Mobile Hamburger -->
    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">🔷</div>
            <span class="logo-text"><?= APP_NAME ?></span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?= publicUrl('dashboard.php') ?>" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>Dashboard
                </a>
                <a href="<?= publicUrl('upload.php') ?>" class="nav-link <?= $currentPage === 'upload.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🔄</span>Face Swap
                </a>
                <a href="<?= publicUrl('jobs.php') ?>" class="nav-link <?= $currentPage === 'jobs.php' ? 'active' : '' ?>">
                    <span class="nav-icon">⚡</span>My Jobs
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <a href="<?= publicUrl('plans.php') ?>" class="nav-link <?= $currentPage === 'plans.php' ? 'active' : '' ?>">
                    <span class="nav-icon">💎</span>Plans
                </a>
                <a href="<?= publicUrl('profile.php') ?>" class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    <span class="nav-icon">👤</span>Profile
                </a>
                <a href="<?= publicUrl('contact.php') ?>" class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                    <span class="nav-icon">✉️</span>Contact
                </a>
            </div>

            <?php if (isAdmin()): ?>
            <div class="nav-section">
                <div class="nav-section-title">Admin</div>
                <a href="<?= url('admin/index.php') ?>" class="nav-link">
                    <span class="nav-icon">🛡️</span>Admin Panel
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= e(currentUserName()) ?></div>
                    <div class="user-role"><?= e(currentUserRole()) ?></div>
                </div>
            </div>
            <a href="<?= publicUrl('logout.php') ?>" class="nav-link mt-1" style="color: var(--accent-red);">
                <span class="nav-icon">🚪</span>Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <?php
        // Display flash messages
        $flash = getFlash();
        if ($flash):
        ?>
        <div class="alert alert-<?= e($flash['type']) ?>" id="flashMessage">
            <span><?= $flash['message'] ?></span>
            <button class="alert-dismiss" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endif; ?>

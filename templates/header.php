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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%2300e5ff'/></svg>">
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
            <span class="logo-text"><?= APP_NAME ?></span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?= publicUrl('dashboard.php') ?>" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span><span class="nav-text">Dashboard</span>
                </a>
                <a href="<?= publicUrl('upload.php') ?>" class="nav-link <?= $currentPage === 'upload.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="refresh-cw"></i></span><span class="nav-text">Face Swap</span>
                </a>
                <a href="<?= publicUrl('jobs.php') ?>" class="nav-link <?= $currentPage === 'jobs.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="zap"></i></span><span class="nav-text">My Jobs</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <a href="<?= publicUrl('plans.php') ?>" class="nav-link <?= $currentPage === 'plans.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="gem"></i></span><span class="nav-text">Plans</span>
                </a>
                <a href="<?= publicUrl('profile.php') ?>" class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="user"></i></span><span class="nav-text">Profile</span>
                </a>
                <a href="<?= publicUrl('contact.php') ?>" class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="mail"></i></span><span class="nav-text">Contact</span>
                </a>
            </div>

            <?php if (isAdmin()): ?>
            <div class="nav-section">
                <div class="nav-section-title">Admin</div>
                <a href="<?= url('admin/index.php') ?>" class="nav-link">
                    <span class="nav-icon"><i data-lucide="shield"></i></span><span class="nav-text">Admin Panel</span>
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
                <span class="nav-icon"><i data-lucide="log-out"></i></span><span class="nav-text">Logout</span>
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

<?php
/**
 * Neuromax – Admin Header Template
 * 
 * Shared layout for all admin pages.
 * Includes admin-specific sidebar navigation.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> – <?= APP_NAME ?> Admin</title>
    <link rel="stylesheet" href="<?= assetUrl('css/style.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23ff0055'/></svg>">
</head>
<body>
<div class="app-layout">

    <!-- Mobile Hamburger -->
    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Admin Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <span class="logo-text"><?= APP_NAME ?></span>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i data-lucide="chevron-left" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Admin</div>
                <a href="<?= url('admin/index.php') ?>" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span><span class="nav-text">Dashboard</span>
                </a>
                <a href="<?= url('admin/users.php') ?>" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="users"></i></span><span class="nav-text">Users</span>
                </a>
                <a href="<?= url('admin/jobs.php') ?>" class="nav-link <?= $currentPage === 'jobs.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="zap"></i></span><span class="nav-text">Jobs</span>
                </a>
                <a href="<?= url('admin/subscriptions.php') ?>" class="nav-link <?= $currentPage === 'subscriptions.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="gem"></i></span><span class="nav-text">Subscriptions</span>
                </a>
                <a href="<?= url('admin/contacts.php') ?>" class="nav-link <?= $currentPage === 'contacts.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><i data-lucide="mail"></i></span><span class="nav-text">Contacts</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Quick Links</div>
                <a href="<?= publicUrl('dashboard.php') ?>" class="nav-link">
                    <span class="nav-icon"><i data-lucide="home"></i></span><span class="nav-text">User Dashboard</span>
                </a>
                <a href="<?= publicUrl('upload.php') ?>" class="nav-link">
                    <span class="nav-icon"><i data-lucide="upload"></i></span><span class="nav-text">Upload</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar" style="background: linear-gradient(135deg, #ef4444, #f59e0b);">
                    <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= e(currentUserName()) ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="<?= publicUrl('logout.php') ?>" class="nav-link mt-1" style="color: var(--accent-red);">
                <span class="nav-icon"><i data-lucide="log-out"></i></span><span class="nav-text">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
        <div class="alert alert-<?= e($flash['type']) ?>" id="flashMessage">
            <span><?= $flash['message'] ?></span>
            <button class="alert-dismiss" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endif; ?>

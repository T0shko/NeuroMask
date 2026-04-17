<?php
/**
 * NeuroMask – Dashboard Template
 * Concept: The Laboratory Workspace
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
    <title>Workspace – <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= assetUrl('css/design-system.css') ?>">
    <link rel="stylesheet" href="<?= assetUrl('css/dashboard.css') ?>">
</head>
<body>

<!-- Laboratory Menu Toggle (Mobile) -->
<button class="laboratory-menu-toggle" id="laboratoryToggle" aria-label="Toggle menu">
    <span></span><span></span><span></span>
</button>
<div class="menu-overlay" id="menuOverlay"></div>

<!-- Laboratory Layout -->
<div class="studio-layout">

    <!-- Identity Rail (Sidebar) -->
    <aside class="identity-rail" id="identityRail">
        <div class="identity-mark">
            <div class="identity-mark-logo">N</div>
            <span class="identity-mark-text">NeuroMask</span>
        </div>

        <nav class="rail-nav">
            <div class="rail-group-title">Workspace</div>
            <a href="<?= publicUrl('dashboard-new.php') ?>" class="rail-link <?= $currentPage === 'dashboard-new.php' ? 'active' : '' ?>">
                <span class="rail-icon">◐</span>
                <span class="rail-label">Overview</span>
            </a>
            <a href="<?= publicUrl('upload.php') ?>" class="rail-link <?= $currentPage === 'upload.php' ? 'active' : '' ?>">
                <span class="rail-icon">◈</span>
                <span class="rail-label">Transmute</span>
            </a>
            <a href="<?= publicUrl('jobs.php') ?>" class="rail-link <?= $currentPage === 'jobs.php' ? 'active' : '' ?>">
                <span class="rail-icon">◇</span>
                <span class="rail-label">Specimens</span>
            </a>

            <div class="rail-group-title">Laboratory</div>
            <a href="<?= publicUrl('plans.php') ?>" class="rail-link <?= $currentPage === 'plans.php' ? 'active' : '' ?>">
                <span class="rail-icon">◆</span>
                <span class="rail-label">Access</span>
            </a>
            <a href="<?= publicUrl('profile.php') ?>" class="rail-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                <span class="rail-icon">◉</span>
                <span class="rail-label">Identity</span>
            </a>
            <a href="<?= publicUrl('contact.php') ?>" class="rail-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                <span class="rail-icon">⬢</span>
                <span class="rail-label">Contact</span>
            </a>

            <?php if (isAdmin()): ?>
            <div class="rail-group-title">Oversight</div>
            <a href="<?= url('admin/index.php') ?>" class="rail-link">
                <span class="rail-icon">▣</span>
                <span class="rail-label">Control</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="identity-dock">
            <div class="identity-dock-user">
                <div class="identity-dock-avatar">
                    <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
                </div>
                <div>
                    <div class="identity-dock-name"><?= e(currentUserName()) ?></div>
                    <div class="identity-dock-role"><?= e(currentUserRole()) ?></div>
                </div>
            </div>
            <a href="<?= publicUrl('logout.php') ?>" class="rail-link" style="margin-top: 12px; color: var(--identity-rose);">
                <span class="rail-icon">⟲</span>
                <span class="rail-label">Exit</span>
            </a>
        </div>
    </aside>

    <!-- Studio Canvas (Main Content) -->
    <main class="studio-canvas">
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

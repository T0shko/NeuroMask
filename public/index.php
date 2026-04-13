<?php
/**
 * Neuromax – Landing Page
 * 
 * Public-facing marketing page with hero, features, and pricing preview.
 * Redirects to dashboard if already logged in.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> – <?= APP_TAGLINE ?></title>
    <meta name="description" content="Swap faces between photos using AI-powered deepfake technology. Upload source and target images for instant, realistic face transformations.">
    <link rel="stylesheet" href="<?= assetUrl('css/style.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔷</text></svg>">
</head>
<body>
<div class="landing-page">

    <!-- Navigation -->
    <nav class="landing-nav">
        <div class="nav-brand">
            <div class="brand-icon">🔷</div>
            <span class="brand-text"><?= APP_NAME ?></span>
        </div>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="<?= publicUrl('dashboard.php') ?>" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="<?= publicUrl('login.php') ?>" class="btn btn-secondary btn-sm">Log In</a>
                <a href="<?= publicUrl('register.php') ?>" class="btn btn-primary btn-sm">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <!-- Animated Particles -->
        <div class="particles" id="particles"></div>

        <div class="hero-content">
            <div class="hero-badge">
                ✨ Powered by Neural AI Technology
            </div>
            <h1>
                Swap Faces with<br>
                <span class="gradient-text">AI DeepFake Technology</span>
            </h1>
            <p>
                Upload a source face and a target photo — our AI seamlessly swaps
                faces using real neural network processing. Realistic, fast, and powerful.
            </p>
            <div class="hero-actions">
                <a href="<?= publicUrl('register.php') ?>" class="btn btn-primary btn-lg">
                    🚀 Start Free
                </a>
                <a href="#features" class="btn btn-secondary btn-lg">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="section-header">
            <h2>Why Choose <?= APP_NAME ?>?</h2>
            <p>State-of-the-art face transformation technology, accessible to everyone.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>Real DeepFake AI</h3>
                <p>Powered by InsightFace and OpenCV — real neural face detection and swapping, not basic filters.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Instant Face Swap</h3>
                <p>Upload a source face and a target photo. The AI detects both faces and seamlessly swaps them.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📸</div>
                <h3>Upload or Webcam</h3>
                <p>Drag and drop images or capture directly from your webcam. Supports JPG and PNG formats.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Face Login</h3>
                <p>Set up biometric face recognition for quick and futuristic login to your account.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Job Dashboard</h3>
                <p>Track all your face swap jobs. View source, target, and result with side-by-side comparison.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <h3>Flexible Plans</h3>
                <p>Start free with Basic, or unlock unlimited face swaps with Pro and Ultra plans.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    <section class="features-section" id="pricing" style="background: var(--bg-secondary);">
        <div class="section-header">
            <h2>Simple, Transparent Pricing</h2>
            <p>Choose the plan that fits your needs. Upgrade or downgrade anytime.</p>
        </div>

        <div class="pricing-grid" style="margin: 0 auto;">
            <div class="pricing-card">
                <div class="plan-icon" style="background: rgba(107, 114, 128, 0.15);">🆓</div>
                <div class="plan-name">Basic</div>
                <div class="plan-price">$0<span>/mo</span></div>
                <div class="plan-description">Perfect for trying out</div>
                <ul class="plan-features">
                    <li>5 face swaps/month</li>
                    <li>Standard quality</li>
                    <li>720p output</li>
                    <li>Email support</li>
                </ul>
                <a href="<?= publicUrl('register.php') ?>" class="btn btn-secondary btn-block">Start Free</a>
            </div>

            <div class="pricing-card featured">
                <div class="plan-icon" style="background: rgba(59, 130, 246, 0.15);">🚀</div>
                <div class="plan-name">Pro</div>
                <div class="plan-price">$19<span>.99/mo</span></div>
                <div class="plan-description">For power users</div>
                <ul class="plan-features">
                    <li>50 face swaps/month</li>
                    <li>HD quality</li>
                    <li>1080p output</li>
                    <li>Priority support</li>
                    <li>Face login</li>
                </ul>
                <a href="<?= publicUrl('register.php') ?>" class="btn btn-primary btn-block">Get Pro</a>
            </div>

            <div class="pricing-card">
                <div class="plan-icon" style="background: rgba(139, 92, 246, 0.15);">👑</div>
                <div class="plan-name">Ultra</div>
                <div class="plan-price">$49<span>.99/mo</span></div>
                <div class="plan-description">Unlimited everything</div>
                <ul class="plan-features">
                    <li>Unlimited face swaps</li>
                    <li>Max quality</li>
                    <li>4K output</li>
                    <li>24/7 priority support</li>
                    <li>Face login</li>
                    <li>API access</li>
                    <li>Batch processing</li>
                </ul>
                <a href="<?= publicUrl('register.php') ?>" class="btn btn-outline btn-block">Get Ultra</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved. Built with 🤖 and ❤️</p>
    </footer>

</div>

<!-- Particle Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('particles');
    if (!container) return;
    
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = (Math.random() * 8 + 6) + 's';
        particle.style.animationDelay = (Math.random() * 5) + 's';
        particle.style.width = (Math.random() * 4 + 2) + 'px';
        particle.style.height = particle.style.width;
        container.appendChild(particle);
    }
});
</script>
<script src="<?= assetUrl('js/app.js') ?>"></script>
</body>
</html>

<?php
/**
 * NeuroMask Landing Page – Transmutation Laboratory
 * An immersive entrance to the identity transformation platform.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NeuroMask: The Laboratory of Digital Identity Transmutation. Transform faces through neural AI technology.">
    <title><?= APP_NAME ?> – Digital Identity Transmutation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= assetUrl('css/design-system.css') ?>">
    <link rel="stylesheet" href="<?= assetUrl('css/landing.css') ?>">
</head>
<body>

<!-- Lab Entrance: Immersive Hero Section -->
<section class="lab-entrance" id="entrance">
    <div class="biometric-grid"></div>
    <div class="scanning-line"></div>
    <div class="orbital orbital-1"></div>
    <div class="orbital orbital-2"></div>
    <div class="orbital orbital-3"></div>

    <!-- Lab Navigation -->
    <nav class="lab-nav">
        <div class="lab-nav-brand">
            <div class="lab-mark">N</div>
            <div class="lab-name">NeuroMask</div>
        </div>
        <div class="lab-nav-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= publicUrl('dashboard.php') ?>" class="btn-identity btn-ghost-identity">Enter Lab</a>
            <?php else: ?>
                <a href="<?= publicUrl('login.php') ?>" class="btn-identity btn-ghost-identity">Log In</a>
                <a href="<?= publicUrl('register.php') ?>" class="btn-identity btn-primary-identity">Begin</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Content -->
    <div class="lab-content">
        <div class="lab-badge">
            <span>◆</span>
            Powered by Neural AI
        </div>
        <h1 class="lab-title">
            The Laboratory of<br>
            <span class="gradient-word">Identity Transmutation</span>
        </h1>
        <p class="lab-statement">
            Enter a space where faces become fluid, where identity is not fixed but waiting
            to be reimagined. Upload your source, provide your target — witness the
            transformation unfold.
        </p>
        <div class="lab-actions">
            <a href="<?= publicUrl('register.php') ?>" class="btn-identity btn-primary-identity">
                <span>Enter</span>
                <span>→</span>
            </a>
            <a href="#process" class="btn-identity btn-ghost-identity">
                <span>Wander</span>
            </a>
        </div>
    </div>
</section>

<!-- Process Chamber: Visual Demonstration -->
<section class="process-chamber" id="process">
    <div class="chamber-intro">
        <h2 class="chamber-title">The Transmutation Process</h2>
        <p class="chamber-subtitle">Observe how identity flow through neural pathways</p>
    </div>

    <div class="transmutation-room">
        <div class="room-stage">
            <!-- Source Pod -->
            <div class="room-pod room-pod-source">
                <div class="room-pod-placeholder">
                    <div class="room-pod-icon">◐</div>
                    <div class="room-pod-label">Source Identity</div>
                </div>
            </div>

            <!-- Process Arrow -->
            <div class="process-arrow">↝</div>

            <!-- Target Pod -->
            <div class="room-pod room-pod-target">
                <div class="room-pod-placeholder">
                    <div class="room-pod-icon">◑</div>
                    <div class="room-pod-label">Target Canvas</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Capabilities Gallery -->
<section class="capabilities-gallery">
    <div class="gallery-intro">
        <h2>Laboratory Capabilities</h2>
        <p>The tools at your disposal for identity exploration</p>
    </div>

    <div class="capabilities-grid">
        <div class="capability-display">
            <div class="capability-icon">⬡</div>
            <h3 class="capability-title">Neural Transmutation</h3>
            <p class="capability-description">
                Powered by InsightFace and deep neural networks — authentic face detection and
                seamless swapping. Not filters, but transformation.
            </p>
        </div>

        <div class="capability-display">
            <div class="capability-icon">▣</div>
            <h3 class="capability-title">Instant Identity Flow</h3>
            <p class="capability-description">
                Provide a source face and a target photograph. The AI perceives both specimens
                and weaves their essence together in seconds.
            </p>
        </div>

        <div class="capability-display">
            <div class="capability-icon">◈</div>
            <h3 class="capability-title">Capture or Upload</h3>
            <p class="capability-description">
                Drag visual specimens through the interface or capture directly via webcam.
                Accepts JPG and PNG formats.
            </p>
        </div>

        <div class="capability-display">
            <div class="capability-icon">◆</div>
            <h3 class="capability-title">Biometric Entry</h3>
            <p class="capability-description">
                Register your facial signature for swift, futuristic laboratory access.
                Identity verified at a glance.
            </p>
        </div>

        <div class="capability-display">
            <div class="capability-icon">◇</div>
            <h3 class="capability-title">Transformation Archive</h3>
            <p class="capability-description">
                Every transmutation preserved. Browse source, target, and result side by side.
                Your identity laboratory in full view.
            </p>
        </div>

        <div class="capability-display">
            <div class="capability-icon">⬢</div>
            <h3 class="capability-title">Tiered Access</h3>
            <p class="capability-description">
                Begin with complimentary experimentation. Fashion your identity exploration
                with expanded laboratory access.
            </p>
        </div>
    </div>
</section>

<!-- Access Laboratory: Pricing -->
<section class="access-laboratory" id="access">
    <div class="access-intro">
        <h2>Laboratory Access</h2>
        <p>Select your level of exploration</p>
    </div>

    <div class="access-tiers">
        <!-- Basic Tier -->
        <div class="tier-vial">
            <div class="tier-essence">◐</div>
            <h3 class="tier-name">Experiment</h3>
            <div class="tier-price">$0<span>/cycle</span></div>
            <p class="tier-caption">For initial exploration</p>
            <ul class="tier-capabilities">
                <li>5 transmutations cycle</li>
                <li>Standard fidelity</li>
                <li>720p output resolution</li>
                <li>Email correspondence</li>
            </ul>
            <a href="<?= publicUrl('register.php') ?>" class="btn-identity btn-ghost-identity btn-block">
                Commence
            </a>
        </div>

        <!-- Pro Tier -->
        <div class="tier-vial tier-vialfeatured">
            <div class="tier-essence">◑</div>
            <h3 class="tier-name">Scholar</h3>
            <div class="tier-price">$19<span>.99</span><span>/cycle</span></div>
            <p class="tier-caption">For dedicated study</p>
            <ul class="tier-capabilities">
                <li>50 transmutations cycle</li>
                <li>High fidelity</li>
                <li>1080p output resolution</li>
                <li>Priority correspondence</li>
                <li>Biometric entry</li>
            </ul>
            <a href="<?= publicUrl('register.php') ?>" class="btn-identity btn-primary-identity btn-block">
                Enroll
            </a>
        </div>

        <!-- Ultra Tier -->
        <div class="tier-vial">
            <div class="tier-essence">◉</div>
            <h3 class="tier-name">Expert</h3>
            <div class="tier-price">$49<span>.99</span><span>/cycle</span></div>
            <p class="tier-caption">Unlimited exploration</p>
            <ul class="tier-capabilities">
                <li>Unlimited transmutations</li>
                <li>Maximum fidelity</li>
                <li>4K output resolution</li>
                <li>Dedicated correspondence</li>
                <li>Biometric entry</li>
                <li>API access</li>
                <li>Batch processing</li>
            </ul>
            <a href="<?= publicUrl('register.php') ?>" class="btn-identity btn-ghost-identity btn-block">
                Elevate
            </a>
        </div>
    </div>
</section>

<!-- Laboratory Footer -->
<footer class="lab-footer">
    <p class="lab-footer-text">
        &copy; <?= date('Y') ?> <?= APP_NAME ?> — Crafted in the laboratory
    </p>
</footer>

<!-- Laboratory Interactions -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Smooth navigation to sections
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Orbital rotation animation
    const orbitals = document.querySelectorAll('.orbital');
    orbitals.forEach((orbital, index) => {
        const duration = 20 + (index * 10);
        orbital.style.animation = `slowRotate ${duration}s linear infinite`;
    });

    // Add slowRotate keyframes dynamically
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slowRotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    // Apply to sections for reveal animations
    document.querySelectorAll('.capability-display, .tier-vial').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        observer.observe(el);
    });
});
</script>

</body>
</html>

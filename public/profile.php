<?php
/**
 * Neuromax – User Profile
 * 
 * Edit profile, change password, view subscription, manage face login.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Subscription.php';
require_once __DIR__ . '/../app/models/FaceData.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    require_once __DIR__ . '/../app/controllers/ProfileController.php';
    $controller = new ProfileController();
    $controller->update();
    exit;
}

$userModel = new User();
$subModel = new Subscription();
$faceModel = new FaceData();

$userId = currentUserId();
$user = $userModel->findById($userId);
$subscription = $subModel->getUserSubscription($userId);
$hasFaceData = $faceModel->findByUser($userId) !== null;

$pageTitle = 'Profile';
$pageDescription = 'Manage your profile settings';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>Profile Settings</h1>
    <p>Manage your account information and preferences.</p>
</div>

<div class="page-content">

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

        <!-- Profile Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <?= csrfField() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($user['name']) ?>" required minlength="2">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($user['email']) ?>" required>
                    </div>
                </div>

                <div class="profile-section">
                    <div class="section-title">Change Password</div>
                    <p class="text-muted text-sm mb-2">Leave blank to keep current password.</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control"
                                   placeholder="Min 8 characters" minlength="8">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                   placeholder="Repeat new password">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </form>
        </div>

        <!-- Sidebar Info -->
        <div>
            <!-- Current Plan -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Subscription</h3>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 40px; margin-bottom: 8px;"></div>
                    <div style="font-size: 20px; font-weight: 700; font-family: 'Outfit', sans-serif;">
                        <?= e($subscription['name'] ?? 'Basic') ?> Plan
                    </div>
                    <div class="text-muted text-sm mt-1">
                        $<?= number_format((float)($subscription['price'] ?? 0), 2) ?>/month
                    </div>
                    <?php if ($subscription): ?>
                    <div class="text-muted text-sm mt-1">
                        Since <?= formatDate($subscription['start_date']) ?>
                    </div>
                    <?php endif; ?>
                    <a href="<?= publicUrl('plans.php') ?>" class="btn btn-outline btn-sm mt-2">Change Plan</a>
                </div>
            </div>

            <!-- Face Login -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Face Login</h3>
                </div>
                <?php if ($hasFaceData): ?>
                    <div style="text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 8px;"></div>
                        <p class="text-sm text-muted">Face enrolled successfully.</p>
                        <button class="btn btn-secondary btn-sm mt-2" id="reEnrollFace">Re-enroll Face</button>
                        <button class="btn btn-danger btn-sm mt-1" id="removeFace">Remove</button>
                    </div>
                <?php else: ?>
                    <div style="text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 8px;">🧑‍💻</div>
                        <p class="text-sm text-muted mb-2">Set up face recognition for quick login.</p>
                        <button class="btn btn-primary btn-sm" id="enrollFace">Setup Face Login</button>
                    </div>
                <?php endif; ?>

                <!-- Webcam for face enrollment -->
                <div id="enrollWebcam" style="display: none; margin-top: 16px;">
                    <div class="webcam-container">
                        <video id="enrollVideo" autoplay muted playsinline></video>
                        <canvas id="enrollCanvas"></canvas>
                    </div>
                    <div class="webcam-controls mt-2">
                        <button class="btn btn-primary btn-sm" id="captureEnroll"> Capture</button>
                        <button class="btn btn-secondary btn-sm" id="cancelEnroll">Cancel</button>
                    </div>
                    <p class="text-sm text-muted mt-1 text-center" id="enrollStatus">Position your face...</p>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Account</h3>
                </div>
                <div class="text-sm">
                    <p><strong>Member since:</strong> <?= formatDate($user['created_at']) ?></p>
                    <p class="mt-1"><strong>Role:</strong> <span class="badge <?= $user['role'] === 'admin' ? 'badge-info' : 'badge-secondary' ?>"><?= e(ucfirst($user['role'])) ?></span></p>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js',
    assetUrl('js/face-login.js')
];
require_once __DIR__ . '/../templates/footer.php';
?>

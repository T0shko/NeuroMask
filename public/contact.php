<?php
/**
 * Neuromax – Contact Page
 * 
 * Contact form for users to send messages/support requests.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/ContactController.php';
    $controller = new ContactController();
    $controller->store();
    exit;
}

$pageTitle = 'Contact';
$pageDescription = 'Get in touch with us';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>Contact Us</h1>
    <p>Have a question or feedback? We'd love to hear from you.</p>
</div>

<div class="page-content">

    <div style="max-width: 640px;">
        <div class="card">
            <form method="POST" action="">
                <?= csrfField() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e(currentUserName()) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($_SESSION['user_email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control"
                           placeholder="What is this about?" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5"
                              placeholder="Tell us more..." required minlength="10"></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    ✉️ Send Message
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

<?php
/**
 * Neuromax – Profile Controller
 * 
 * Handles profile view and update.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/User.php';

class ProfileController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Update user profile.
     */
    public function update(): void
    {
        requireLogin();
        requireCsrf();

        $userId = currentUserId();
        $errors = [];

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        // Check email uniqueness (exclude current user)
        $existing = $this->userModel->findByEmail($email);
        if ($existing && (int)$existing['id'] !== $userId) {
            $errors[] = 'This email is already taken by another user.';
        }

        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            redirect(publicUrl('profile.php'));
        }

        $updateData = ['name' => $name, 'email' => $email];

        // Handle password change (optional)
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                setFlash('error', 'New password must be at least 8 characters.');
                redirect(publicUrl('profile.php'));
            }
            if ($newPassword !== $confirmPassword) {
                setFlash('error', 'New passwords do not match.');
                redirect(publicUrl('profile.php'));
            }
            $updateData['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $this->userModel->update($userId, $updateData);

        // Update session
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        setFlash('success', 'Profile updated successfully.');
        redirect(publicUrl('profile.php'));
    }
}

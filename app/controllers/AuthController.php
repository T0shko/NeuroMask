<?php
/**
 * Neuromax – Auth Controller
 * 
 * Handles user registration, login, and logout.
 * All inputs are validated and sanitized.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Subscription.php';

class AuthController
{
    private User $userModel;
    private Subscription $subscriptionModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Handle user registration.
     */
    public function register(): void
    {
        // Validate CSRF
        requireCsrf();

        $errors = [];

        // ── Sanitize inputs ──
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        // ── Validate ──
        if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // Check if email already exists
        if (empty($errors) && $this->userModel->findByEmail($email)) {
            $errors[] = 'This email is already registered.';
        }

        // ── Handle errors ──
        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            redirect(publicUrl('register.php'));
        }

        // ── Create user ──
        $userId = $this->userModel->create([
            'name'     => $name,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role'     => 'user',
        ]);

        // Auto-assign Basic (free) subscription
        $this->subscriptionModel->assignToUser($userId, 1);

        // Auto-login after registration
        $user = $this->userModel->findById($userId);
        loginUser($user);

        setFlash('success', 'Welcome to Neuromax! Your account has been created.');
        redirect(publicUrl('dashboard.php'));
    }

    /**
     * Handle user login.
     */
    public function login(): void
    {
        requireCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate inputs
        if (empty($email) || empty($password)) {
            setFlash('error', 'Please enter both email and password.');
            redirect(publicUrl('login.php'));
        }

        // Find user
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            setFlash('error', 'Invalid email or password.');
            redirect(publicUrl('login.php'));
        }

        // Set session
        loginUser($user);

        setFlash('success', 'Welcome back, ' . e($user['name']) . '!');
        redirect(publicUrl('dashboard.php'));
    }

    /**
     * Handle logout.
     */
    public function logout(): void
    {
        logoutUser();
        setFlash('success', 'You have been logged out.');
        redirect(publicUrl('login.php'));
    }
}

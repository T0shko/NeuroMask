<?php
/**
 * Neuromax – Authentication Guards
 * 
 * Middleware-like functions to protect routes.
 * Include this file at the top of any protected page.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        redirect(publicUrl('login.php'));
    }
}

/**
 * Require the user to be an admin.
 * Redirects to dashboard if not admin.
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect(publicUrl('dashboard.php'));
    }
}

/**
 * Redirect logged-in users away from login/register pages.
 */
function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        redirect(publicUrl('dashboard.php'));
    }
}

/**
 * Set session data after successful login.
 */
function loginUser(array $user): void
{
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
}

/**
 * Clear session data and destroy session.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

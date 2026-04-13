<?php
/**
 * Neuromax – Logout
 * Destroys the session and redirects to login.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();
// Start a new session for the flash message
session_start();
setFlash('success', 'You have been logged out successfully.');
redirect(publicUrl('login.php'));

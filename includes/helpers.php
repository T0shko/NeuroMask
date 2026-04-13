<?php
/**
 * Neuromax – Helper Functions
 * 
 * Shared utility functions used across the application.
 */

/**
 * Escape output for safe HTML rendering (XSS protection).
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL and terminate.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set a flash message to display on next page load.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,      // success, error, warning, info
        'message' => $message,
    ];
}

/**
 * Get and clear the current flash message.
 */
function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin.
 */
function isAdmin(): bool
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user's ID from session.
 */
function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's name from session.
 */
function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get current user's role from session.
 */
function currentUserRole(): string
{
    return $_SESSION['user_role'] ?? 'user';
}

/**
 * Format a date string for display.
 */
function formatDate(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) return '—';
    return date($format, strtotime($date));
}

/**
 * Format a date with time.
 */
function formatDateTime(?string $date): string
{
    return formatDate($date, 'M j, Y \a\t g:i A');
}

/**
 * Generate a URL relative to the app base.
 */
function url(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Generate a URL for public pages.
 */
function publicUrl(string $page): string
{
    return PUBLIC_URL . '/' . ltrim($page, '/');
}

/**
 * Generate a URL for assets.
 */
function assetUrl(string $path): string
{
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Get the file extension from a filename.
 */
function getFileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate a unique filename preserving extension.
 */
function generateUniqueFilename(string $originalName): string
{
    $ext = getFileExtension($originalName);
    return uniqid('nm_', true) . '.' . $ext;
}

/**
 * Truncate a string to a max length with ellipsis.
 */
function truncate(string $text, int $length = 50): string
{
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Get the status badge CSS class for a job status.
 */
function statusBadgeClass(string $status): string
{
    return match($status) {
        'pending'    => 'badge-warning',
        'processing' => 'badge-info',
        'completed'  => 'badge-success',
        'failed'     => 'badge-danger',
        'active'     => 'badge-success',
        'expired'    => 'badge-danger',
        'cancelled'  => 'badge-secondary',
        default      => 'badge-secondary',
    };
}

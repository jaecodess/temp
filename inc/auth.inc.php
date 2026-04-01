<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Check if the current user is logged in.
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/*
 * Check if the current user is an admin.
 */
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/*
 * Redirect to login page if user is not logged in.
 */
function require_login()
{
    if (!is_logged_in()) {
        header("Location: /login.php");
        exit;
    }
}

/*
 * Redirect to 403 page if user is not admin.
 */
function require_admin()
{
    require_login();
    if (!is_admin()) {
        header("Location: /403.php");
        exit;
    }
}

/*
 * Sanitize user input.
 */
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/*
 * Generate a CSRF token for the current session.
 */
function generate_csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/*
 * Verify a submitted CSRF token against the session token.
 */
function verify_csrf_token($token)
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

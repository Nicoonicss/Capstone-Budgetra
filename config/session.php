<?php
// Session Management

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Require login
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/src/auth/login.php');
        exit;
    }
}

<?php



// src/helpers/auth.php
//handles user sessions, login,logout, and CSRF protection
session_start(); // ensure session is started regardless of where this file is included


// Generates and stores a CSRF token in the session to prevent cross-site request forgery attacks
function ensureCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

// Retrieves the CSRF token from the session for use in forms
function getCsrfToken(): ?string {
    return $_SESSION['csrf_token'] ?? null;
}

// Checks if a user is currently logged in by verifying a user object exists in the session
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

// Redirects unauthenticated users to the login page and stops execution
function requireLogin() {
    if (!isLoggedIn()) {
        // Redirect to login page if user is not authenticated
        header('Location: /mini-blog/public/login.php');
        exit;
    }
}

// Stores user data in the session after successful login
function loginUser(array $user) {
    // do not store password hash in session
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
}

// Securely destroys the user session and clears all session data
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Destroy the server-side session data
    session_destroy();
}

// Returns the currently logged-in user object, or null if no user is authenticated
function currentUser() {
    return $_SESSION['user'] ?? null;
}


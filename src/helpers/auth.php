<?php



// src/helpers/auth.php
session_start(); // ensure session is started if included directly from public files

function ensureCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfToken(): ?string {
    return $_SESSION['csrf_token'] ?? null;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /mini-blog/public/login.php');
        exit;
    }
}

function loginUser(array $user) {
    // do not store password hash in session
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
}

function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}


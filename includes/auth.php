<?php
/**
 * Authentication Functions
 * 
 * This file contains functions related to user authentication for the YankesDokpol application.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

/**
 * Authenticate a user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return array|bool User data array if authenticated, false otherwise
 */
function authenticateUser($username, $password) {
    $username = sanitizeInput($username);
    
    $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
    $result = executeQuery($sql, [$username]);
    
    if ($result && $user = fetchRow($result)) {
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login time
            executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            
            // Remove password from user data before returning
            unset($user['password']);
            return $user;
        }
    }
    
    return false;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 * 
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    $sql = "SELECT id, username, nama_lengkap, role, last_login FROM users WHERE id = ?";
    $result = executeQuery($sql, [$userId]);
    
    if ($result && $user = fetchRow($result)) {
        return $user;
    }
    
    return null;
}

/**
 * Log out current user
 * 
 * @return void
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if current user has specific role
 * 
 * @param string|array $roles Role(s) to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($currentUser['role'], $roles);
    } else {
        return $currentUser['role'] === $roles;
    }
}

/**
 * Require authentication to access a page
 * If not logged in, redirect to login page
 * 
 * @param string|array $roles Optional role(s) required to access the page
 * @return void
 */
function requireLogin($roles = null) {
    if (!isLoggedIn()) {
        setFlashMessage('Anda harus login terlebih dahulu', 'warning');
        redirect('login.php');
    }
    
    if ($roles !== null && !hasRole($roles)) {
        setFlashMessage('Anda tidak memiliki akses ke halaman ini', 'danger');
        redirect('form_peserta.php');
    }
}

/**
 * Redirect if user is already logged in
 * Useful for login page to prevent logged in users from accessing it
 * 
 * @param string $redirectUrl URL to redirect to
 * @return void
 */
function redirectIfLoggedIn($redirectUrl = 'index.php') {
    if (isLoggedIn()) {
        redirect($redirectUrl);
    }
}

/**
 * Require admin role to access a page
 * If not admin, redirect to dashboard
 * 
 * @return void
 */
function requireAdmin() {
    if (!isLoggedIn() || !hasRole('admin')) {
        setFlashMessage('Anda tidak memiliki akses ke halaman ini', 'danger');
        redirect('dashboard.php');
    }
}

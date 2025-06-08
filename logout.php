<?php
/**
 * Logout Page
 * 
 * This file handles user logout for the YankesDokpol application.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Log out user
logoutUser();

// Set success message
setFlashMessage('Anda telah berhasil logout', 'info');

// Redirect to login page
redirect('login.php');

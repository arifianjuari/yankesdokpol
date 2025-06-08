<?php

/**
 * Login Page
 * 
 * This file handles user authentication for the YankesDokpol application.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
redirectIfLoggedIn();

// Set page title
$pageTitle = 'YankesDokpol - Login';
$loginError = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate form data
    if (empty($username) || empty($password)) {
        $loginError = 'Username dan password harus diisi';
    } else {
        // Attempt to authenticate user
        $user = authenticateUser($username, $password);

        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            // Set success message
            setFlashMessage('Login berhasil. Selamat datang!', 'success');

            // Redirect to dashboard
            redirect('dashboard.php');
        } else {
            $loginError = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <?php include 'includes/pwa_head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-logo img {
            max-width: 150px;
        }

        .login-form {
            margin-top: 20px;
        }

        .login-links {
            margin-top: 20px;
            text-align: center;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(46,125,50,0.1) 0%, rgba(255,152,0,0.1) 100%);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <?php
        // Display flash messages if any
        $flashMessage = getFlashMessage();
        if ($flashMessage) {
            echo '<div class="alert alert-' . $flashMessage['type'] . ' alert-dismissible fade show" role="alert">';
            echo $flashMessage['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        ?>

        <div class="login-container">
            <div class="login-logo">
                <h2 style="color: #2e7d32;">HUT Bhayangkara ke-79</h2>
                <p style="color: #795548;">Pemeriksaan Gratis kepada Pengemudi Ojek Daring/Online</p>
            </div>

            <?php if (!empty($loginError)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $loginError; ?>
                </div>
            <?php endif; ?>

            <div class="login-form">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>

            <div class="login-links">
                <a href="form_peserta.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Form Pendaftaran
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/pwa.js"></script>
    
    <?php include 'includes/pwa_install_button.php'; ?>
</body>

</html>
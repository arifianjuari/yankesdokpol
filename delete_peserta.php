<?php
/**
 * Delete Peserta
 * 
 * Script untuk menghapus data peserta berdasarkan NIK
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

// Require login and admin role to access this page
requireLogin();
requireAdmin();

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Metode tidak valid.');
    header('Location: index.php');
    exit;
}

// Validate NIK parameter
if (!isset($_POST['nik']) || empty($_POST['nik'])) {
    setFlashMessage('danger', 'NIK tidak valid.');
    header('Location: index.php');
    exit;
}

$nik = $_POST['nik'];

// Begin transaction
$conn->begin_transaction();

try {
    // Delete from peserta_layanan table first (foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM peserta_layanan WHERE nik = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    
    // Delete from peserta table
    $stmt = $conn->prepare("DELETE FROM peserta WHERE nik = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        // Commit transaction
        $conn->commit();
        setFlashMessage('success', 'Data peserta berhasil dihapus.');
    } else {
        // Rollback transaction
        $conn->rollback();
        setFlashMessage('warning', 'Data peserta tidak ditemukan.');
    }
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    setFlashMessage('danger', 'Gagal menghapus data peserta: ' . $e->getMessage());
}

// Redirect back to index page
header('Location: index.php');
exit;

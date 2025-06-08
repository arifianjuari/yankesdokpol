<?php
/**
 * Proses CRUD Peserta
 * 
 * File ini menangani operasi CRUD untuk data peserta
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Mulai session
session_start();

// Include file yang diperlukan
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/validation.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login untuk mengakses halaman ini';
    header('Location: login.php');
    exit();
}

// Periksa apakah request method valid untuk action yang diminta
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Hanya periksa method POST untuk action update_status
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode request tidak valid';
    header('Location: daftar_peserta.php');
    exit();
}

// Ambil action dan ID dari URL
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada action, redirect ke halaman daftar
if (empty($action)) {
    header('Location: daftar_peserta.php');
    exit();
}

// Proses berdasarkan action
switch ($action) {
    case 'delete':
        handleDelete($id);
        break;
    case 'update_status':
        handleUpdateStatus($nik);
        break;
    default:
        $_SESSION['error'] = 'Aksi tidak valid';
        header('Location: daftar_peserta.php');
        exit();
}

/**
 * Handle delete participant
 */
function handleDelete($id) {
    global $conn;
    
    // Set default response type to HTML (for backward compatibility)
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    function sendResponse($success, $message, $isAjax = false) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success, 'message' => $message]);
            exit();
        } else {
            if ($success) {
                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = $message;
            }
            header('Location: daftar_peserta.php');
            exit();
        }
    }
    
    // Validasi ID
    if (empty($id)) {
        sendResponse(false, 'ID pendaftaran tidak valid', $isAjax);
    }
    
    try {
        // Hapus data dari tabel peserta_layanan
        $deleteQuery = "DELETE FROM peserta_layanan WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Periksa apakah ada referensi peserta yang tidak terpakai
            checkAndCleanupPeserta($conn);
            sendResponse(true, 'Data pendaftaran berhasil dihapus', $isAjax);
        } else {
            throw new Exception('Gagal menghapus data pendaftaran');
        }
    } catch (Exception $e) {
        sendResponse(false, 'Gagal menghapus data pendaftaran: ' . $e->getMessage(), $isAjax);
    }
}

/**
 * Periksa dan hapus data peserta yang tidak terpakai
 */
function checkAndCleanupPeserta($conn) {
    // Hapus peserta yang tidak memiliki referensi di peserta_layanan
    $cleanupQuery = "DELETE p FROM peserta p 
                    LEFT JOIN peserta_layanan pl ON p.nik = pl.nik 
                    WHERE pl.nik IS NULL";
    $conn->query($cleanupQuery);
}

/**
 * Handle update status peserta
 */
function handleUpdateStatus($nik) {
    // Validasi NIK
    if (empty($nik)) {
        echo json_encode(['success' => false, 'message' => 'NIK tidak valid']);
        exit();
    }
    
    // Ambil status baru dari POST data
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    
    // Update status di database
    $query = "UPDATE peserta SET is_active = ? WHERE nik = ?";
    $result = executeQuery($query, [$status, $nik]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
    }
    exit();
}

// Redirect default jika tidak ada action yang sesuai
header('Location: daftar_peserta.php');
exit();

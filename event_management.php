<?php

/**
 * Event Management
 * 
 * This page allows administrators to manage acara (add, edit, deactivate).
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/validation.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Page title
$pageTitle = 'YankesDokpol - Manajemen Acara';

// Process form submission for adding/editing acara
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    $message = '';

    // Sanitize input data
    $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $namaEvent = sanitizeInput($_POST['nama_acara'] ?? '');
    $tanggalMulai = sanitizeInput($_POST['tanggal_mulai'] ?? '');
    $tanggalSelesai = sanitizeInput($_POST['tanggal_selesai'] ?? '');
    $lokasi = sanitizeInput($_POST['lokasi'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $status = isset($_POST['status']) ? 'aktif' : 'selesai';

    // Validate input data
    if (empty($namaEvent)) {
        $errors['nama_acara'] = 'Nama acara tidak boleh kosong';
    }

    if (empty($lokasi)) {
        $errors['lokasi'] = 'Lokasi tidak boleh kosong';
    }

    if (empty($tanggalMulai)) {
        $errors['tanggal_mulai'] = 'Tanggal mulai tidak boleh kosong';
    }

    if (empty($tanggalSelesai)) {
        $errors['tanggal_selesai'] = 'Tanggal selesai tidak boleh kosong';
    } elseif ($tanggalSelesai < $tanggalMulai) {
        $errors['tanggal_selesai'] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai';
    }

    // If no errors, save to database
    if (empty($errors)) {
        if ($eventId > 0) {
            // Update existing acara
            $query = "UPDATE acara 
                      SET nama_acara = ?, 
                          tanggal_mulai = ?, 
                          tanggal_selesai = ?, 
                          lokasi = ?, 
                          deskripsi = ?, 
                          status = ? 
                          WHERE id = ?";
            $params = [$namaEvent, $tanggalMulai, $tanggalSelesai, $lokasi, $deskripsi, $status, $eventId];

            if (executeQuery($query, $params)) {
                $success = true;
                $message = 'Acara berhasil diperbarui';
            } else {
                $errors['db'] = 'Gagal memperbarui acara. Silakan coba lagi.';
            }
        } else {
            // Add new acara
            $query = "INSERT INTO acara (nama_acara, tanggal_mulai, tanggal_selesai, lokasi, deskripsi, status) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$namaEvent, $tanggalMulai, $tanggalSelesai, $lokasi, $deskripsi, $status];

            if (executeQuery($query, $params)) {
                $success = true;
                $message = 'Acara baru berhasil ditambahkan';
            } else {
                $errors['db'] = 'Gagal menambahkan acara. Silakan coba lagi.';
            }
        }
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    
    // Check if there are any related records in peserta_layanan
    $hasRelatedRecords = fetchValue(
        "SELECT COUNT(*) FROM peserta_layanan WHERE acara_id = ?", 
        [$eventId]
    ) > 0;
    
    if ($hasRelatedRecords) {
        $errors['db'] = 'Tidak dapat menghapus acara karena sudah memiliki data peserta terkait.';
    } else {
        if (executeQuery("DELETE FROM acara WHERE id = ?", [$eventId])) {
            $success = true;
            $message = 'Acara berhasil dihapus';
            // Refresh the event list by re-fetching from database
            $acaraList = fetchRows("SELECT * FROM acara ORDER BY tanggal_mulai DESC", []) ?: [];
        } else {
            $errors['db'] = 'Gagal menghapus acara. Silakan coba lagi.';
        }
    }
}
// Handle toggle status action
elseif (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    $currentStatus = fetchValue("SELECT status FROM acara WHERE id = ?", [$eventId]);
    $newStatus = $currentStatus === 'aktif' ? 'selesai' : 'aktif';

    if (executeQuery("UPDATE acara SET status = ? WHERE id = ?", [$newStatus, $eventId])) {
        $statusMessage = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        $success = true;
        $message = "Acara berhasil $statusMessage";
    } else {
        $errors['db'] = 'Gagal mengubah status acara. Silakan coba lagi.';
    }
}

// Handle edit action
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    $editData = fetchRow("SELECT * FROM acara WHERE id = ?", [$eventId]);
    
    if (!$editData) {
        $errors['db'] = 'Acara tidak ditemukan';
    }
}

// Initialize acaraList
$acaraList = [];

// Get all acara data
$acaraList = fetchRows("SELECT * FROM acara ORDER BY tanggal_mulai DESC", []) ?: [];

// Set page content
$pageContent = 'content/event_management_content.php';

// Include admin layout
include 'includes/admin_layout.php';

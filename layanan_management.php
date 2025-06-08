<?php
/**
 * Layanan Management Page
 * 
 * This page provides an interface for managing service data (CRUD operations).
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Koneksi database
global $conn;

// Proses CRUD
$message = '';
$messageType = '';

// Handle form submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nama = trim($_POST['nama_layanan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    
    if (empty($nama)) {
        $message = 'Nama layanan tidak boleh kosong';
        $messageType = 'error';
    } else {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE layanan SET nama_layanan = ?, deskripsi = ?, status = ? WHERE id = ?");
            $stmt->bind_param('ssii', $nama, $deskripsi, $status, $id);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO layanan (nama_layanan, deskripsi, status) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $nama, $deskripsi, $status);
        }
        
        if ($stmt->execute()) {
            $message = 'Data layanan berhasil disimpan';
            $messageType = 'success';
        } else {
            $message = 'Gagal menyimpan data layanan';
            $messageType = 'error';
        }
        
        $stmt->close();
        header('Location: layanan_management.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    if ($deleteId > 0) {
        $stmt = $conn->prepare("DELETE FROM layanan WHERE id = ?");
        $stmt->bind_param('i', $deleteId);
        if ($stmt->execute()) {
            $message = 'Data layanan berhasil dihapus';
            $messageType = 'success';
        } else {
            $message = 'Gagal menghapus data layanan';
            $messageType = 'error';
        }
        $stmt->close();
        header('Location: layanan_management.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

// Get all layanan data
$layananList = [];
$result = $conn->query("SELECT * FROM layanan ORDER BY nama_layanan");
if ($result) {
    $layananList = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Get data for edit
$editData = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    if ($editId > 0) {
        $stmt = $conn->prepare("SELECT * FROM layanan WHERE id = ?");
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editData = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get message from URL if exists
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// Set page content
$pageContent = 'content/layanan_management_content.php';

// Include admin layout
include 'includes/admin_layout.php';

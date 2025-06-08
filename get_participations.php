<?php
/**
 * Get Participations API
 * 
 * Endpoint untuk mendapatkan daftar partisipasi peserta
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set headers
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Get NIK from query string
$nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';

// Validate NIK
if (empty($nik)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'NIK tidak valid']);
    exit();
}

try {
    // Query to get participations
    $query = "SELECT 
                pl.id,
                a.nama_acara,
                l.nama_layanan,
                DATE_FORMAT(pl.created_at, '%d/%m/%Y %H:%i') as tanggal,
                pl.petugas,
                pl.hasil_pemeriksaan,
                pl.foto_kegiatan
              FROM peserta_layanan pl
              LEFT JOIN acara a ON pl.acara_id = a.id
              LEFT JOIN layanan l ON pl.layanan_id = l.id
              WHERE pl.nik = ?
              ORDER BY pl.created_at DESC";
    
    $result = executeQuery($query, [$nik]);
    $participations = fetchRows($result) ?: [];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $participations
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in get_participations.php: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data partisipasi'
    ]);
}

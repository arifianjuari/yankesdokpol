<?php
/**
 * API untuk mengambil data peserta berdasarkan NIK
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Include database configuration
require_once 'config/database.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Pastikan request adalah GET dan parameter NIK ada
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['nik'])) {
    echo json_encode([
        'success' => false,
        'message' => 'NIK tidak ditemukan dalam request'
    ]);
    exit;
}

// Get acara_id if provided
$acara_id = isset($_GET['acara_id']) ? (int)$_GET['acara_id'] : null;

$nik = $_GET['nik'];

// Validasi NIK
if (empty($nik) || strlen($nik) !== 16 || !is_numeric($nik)) {
    echo json_encode([
        'success' => false,
        'message' => 'Format NIK tidak valid'
    ]);
    exit;
}

// Ambil data peserta dari database
$peserta = fetchRow("SELECT * FROM peserta WHERE nik = ?", [$nik]);

if (!$peserta) {
    echo json_encode([
        'success' => false,
        'message' => 'Data peserta tidak ditemukan'
    ]);
    exit;
}

// Ambil data layanan dan data tambahan dari peserta_layanan
if ($acara_id) {
    // If acara_id is provided, filter by both NIK and acara_id
    $peserta_layanan = fetchRows("SELECT layanan_id, acara_id, hasil_pemeriksaan, foto_kegiatan, petugas FROM peserta_layanan WHERE nik = ? AND acara_id = ? ORDER BY id ASC", [$nik, $acara_id]);
} else {
    // Otherwise, get all records for this NIK
    $peserta_layanan = fetchRows("SELECT layanan_id, acara_id, hasil_pemeriksaan, foto_kegiatan, petugas FROM peserta_layanan WHERE nik = ? ORDER BY id ASC", [$nik]);
}

$layananIds = array_column($peserta_layanan, 'layanan_id');
$acaraId = !empty($peserta_layanan) ? $peserta_layanan[0]['acara_id'] : null;

// Ambil data nama layanan
$layanan = [];
if (!empty($layananIds)) {
    $placeholders = implode(',', array_fill(0, count($layananIds), '?'));
    $layanan = fetchRows("SELECT id, nama_layanan FROM layanan WHERE id IN ($placeholders)", $layananIds);
}

// Return data peserta dan layanan dalam format JSON
echo json_encode([
    'success' => true,
    'peserta' => $peserta,
    'layanan' => $layanan,
    'peserta_layanan' => $peserta_layanan
]);

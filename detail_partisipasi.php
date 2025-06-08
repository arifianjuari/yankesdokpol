<?php
/**
 * Detail Partisipasi
 * 
 * Halaman ini menampilkan detail partisipasi peserta
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set page requirements
$requireAuth = true;
$requireAdmin = true;
$pageTitle = 'YankesDokpol - Detail Partisipasi';

// Get NIK from URL
$nik = isset($_GET['nik']) ? sanitizeInput($_GET['nik']) : '';
$nama = '';

// Validate NIK
if (empty($nik)) {
    $_SESSION['error'] = 'NIK tidak valid';
    header('Location: identitas_peserta.php');
    exit();
}

// Get peserta data
$pesertaQuery = "SELECT * FROM peserta WHERE nik = ?";
$pesertaResult = executeQuery($pesertaQuery, [$nik]);
$peserta = fetchRow($pesertaResult);

if (!$peserta) {
    $_SESSION['error'] = 'Data peserta tidak ditemukan';
    header('Location: identitas_peserta.php');
    exit();
}

$nama = $peserta['nama'];

// Get participations data
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
$totalPartisipasi = count($participations);

// Set page content file path
$pageContent = __DIR__ . '/content/detail_partisipasi_content.php';

// Include admin layout
include __DIR__ . '/includes/admin_layout.php';

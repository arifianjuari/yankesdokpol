<?php
/**
 * Identitas Peserta
 * 
 * Halaman ini menampilkan daftar peserta dari tabel 'peserta'
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
$pageTitle = 'YankesDokpol - Identitas Peserta';

// Pagination settings
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = " WHERE p.nik LIKE ? OR p.nama LIKE ? OR p.alamat LIKE ? OR p.nomor_hp LIKE ? ";
    $searchParam = "%{$search}%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

// Count total records for pagination
$totalPeserta = 0;
$countQuery = "SELECT COUNT(*) as total FROM peserta p" . $searchCondition;

if (!empty($params)) {
    $result = executeQuery($countQuery, $params);
} else {
    $result = executeQuery($countQuery, []);
}

if ($result && $row = fetchRow($result)) {
    $totalPeserta = $row['total'];
}

// Calculate total pages
$totalPages = ceil($totalPeserta / $perPage);

// Get peserta data with pagination
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM peserta_layanan pl WHERE pl.nik = p.nik) as total_partisipasi
          FROM peserta p 
          {$searchCondition} 
          ORDER BY p.nama ASC 
          LIMIT {$offset}, {$perPage}";

if (!empty($params)) {
    $result = executeQuery($query, $params);
} else {
    $result = executeQuery($query, []);
}
$pesertaList = fetchRows($result) ?: [];

// Set page content file path
$pageContent = __DIR__ . '/content/identitas_peserta_content.php';

// Include admin layout
include __DIR__ . '/includes/admin_layout.php';

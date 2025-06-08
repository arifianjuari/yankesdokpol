<?php
/**
 * Daftar Peserta
 * 
 * Halaman ini menampilkan daftar peserta dari tabel 'peserta'
 * untuk aplikasi YankesDokpol.
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
$pageTitle = 'YankesDokpol - Daftar Peserta';

// Pagination settings
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Search and filter functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$acaraId = isset($_GET['acara_id']) ? (int)$_GET['acara_id'] : 0;
$searchCondition = '';
$params = [];
$whereConditions = [];

// Build search conditions
if (!empty($search)) {
    $whereConditions[] = "(p.nik LIKE ? OR p.nama LIKE ? OR p.alamat LIKE ? OR p.nomor_hp LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// Add acara filter condition
if ($acaraId > 0) {
    $whereConditions[] = "pl.acara_id = ?";
    $params[] = $acaraId;
}

// Combine all conditions
if (!empty($whereConditions)) {
    $searchCondition = " WHERE " . implode(' AND ', $whereConditions);
}

// Build base query with joins
$baseQuery = "SELECT 
    pl.id as peserta_layanan_id,
    p.*,
    pl.acara_id,
    pl.layanan_id,
    pl.hasil_pemeriksaan,
    pl.foto_kegiatan,
    pl.petugas,
    pl.created_at as waktu_pendaftaran,
    a.nama_acara,
    l.nama_layanan
FROM peserta p
JOIN peserta_layanan pl ON p.nik = pl.nik
LEFT JOIN acara a ON pl.acara_id = a.id
LEFT JOIN layanan l ON pl.layanan_id = l.id";

// Count total records for pagination
$totalPeserta = 0;
$countQuery = "SELECT COUNT(*) as total FROM ($baseQuery $searchCondition) as total_count";
$countParams = $params;

$result = executeQuery($countQuery, $countParams);
if ($result && $row = fetchRow($result)) {
    $totalPeserta = $row['total'];
}

// Calculate total pages
$totalPages = ceil($totalPeserta / $perPage);

// Get acara list for filter dropdown
$acaraList = [];
$acaraQuery = "SELECT id, nama_acara FROM acara ORDER BY nama_acara";
$acaraResult = executeQuery($acaraQuery);
if ($acaraResult) {
    while ($row = fetchRow($acaraResult)) {
        $acaraList[$row['id']] = $row['nama_acara'];
    }
}

// Get peserta_layanan data with pagination, search and filters
$query = "$baseQuery $searchCondition ORDER BY pl.created_at DESC LIMIT {$offset}, {$perPage}";
$result = executeQuery($query, $params);
$pesertaList = fetchRows($result) ?: [];

// Set page content file path
$pageContent = __DIR__ . '/content/daftar_peserta_content.php';

// Include admin layout
include __DIR__ . '/includes/admin_layout.php';

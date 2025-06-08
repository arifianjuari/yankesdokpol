<?php

/**
 * Galeri Page
 * 
 * This page displays a gallery of documentation cards showing participant information,
 * activity timestamps, and examinations performed.
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

// Require login to access this page
requireLogin();

// Get current user data
$currentUser = getCurrentUser();

// Page title
$pageTitle = 'YankesDokpol - Galeri Dokumentasi';

// Pagination settings
$itemsPerPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Filter settings
$layananFilter = isset($_GET['layanan']) ? (int)$_GET['layanan'] : 0;
$acaraFilter = isset($_GET['acara']) ? (int)$_GET['acara'] : 0;
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Get layanan list for filter dropdown
$layananList = [];
$layananQuery = "SELECT id, nama_layanan FROM layanan ORDER BY nama_layanan";
$layananResult = $conn->query($layananQuery);
if ($layananResult) {
    $layananList = $layananResult->fetch_all(MYSQLI_ASSOC);
    $layananResult->free();
}

// Get acara list for filter dropdown
$acaraList = [];
$acaraQuery = "SELECT id, nama_acara, tanggal_mulai FROM acara ORDER BY tanggal_mulai DESC";
$acaraResult = $conn->query($acaraQuery);
if ($acaraResult) {
    $acaraList = $acaraResult->fetch_all(MYSQLI_ASSOC);
    $acaraResult->free();
}

// Build the query conditions
$conditions = ["pl.foto_kegiatan IS NOT NULL AND pl.foto_kegiatan != ''"];
$params = [];
$paramTypes = '';

if ($layananFilter > 0) {
    $conditions[] = "pl.layanan_id = ?";
    $params[] = $layananFilter;
    $paramTypes .= 'i';
}

if ($acaraFilter > 0) {
    $conditions[] = "pl.acara_id = ?";
    $params[] = $acaraFilter;
    $paramTypes .= 'i';
}

if (!empty($searchQuery)) {
    $conditions[] = "(p.nama LIKE ? OR pl.hasil_pemeriksaan LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $paramTypes .= 'ss';
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Count total unique participant-event combinations for pagination
$countQuery = "
    SELECT COUNT(DISTINCT CONCAT(pl.acara_id, '-', pl.nik)) as total 
    FROM peserta_layanan pl
    JOIN peserta p ON pl.nik = p.nik
    JOIN layanan l ON pl.layanan_id = l.id
    $whereClause
";

// Prepare and execute count query
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);
$countStmt->close();

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Get gallery items with additional event information
// Group by acara_id and nik to show only one entry per participant per event
$galleryQuery = "
    SELECT 
        pl.nik,
        p.nama,
        a.nama_acara,
        a.tanggal_mulai,
        MAX(pl.foto_kegiatan) AS foto_kegiatan,
        MAX(pl.created_at) AS created_at,
        COUNT(DISTINCT pl.id) AS total_pemeriksaan
    FROM 
        peserta_layanan pl
    JOIN 
        peserta p ON pl.nik = p.nik
    LEFT JOIN
        acara a ON pl.acara_id = a.id
    $whereClause
    GROUP BY 
        pl.acara_id, pl.nik, p.nama, a.nama_acara, a.tanggal_mulai
    ORDER BY 
        created_at DESC
    LIMIT ? OFFSET ?
";

// Add pagination parameters
$params[] = $itemsPerPage;
$params[] = $offset;
$paramTypes .= 'ii';

// Execute main query
$stmt = $conn->prepare($galleryQuery);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$galleryItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Set content file
$pageContent = 'content/galeri_content.php';

// Include admin layout
include 'includes/admin_layout.php';

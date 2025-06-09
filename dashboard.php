<?php

/**
 * Dashboard Page
 * 
 * This page displays the dashboard with statistics and visualizations
 * for the HUT Bhayangkara ke-79 application.
 * 
 * @package HUTBhayangkara79
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Allow public access, login is not strictly required for dashboard view
// $currentUser = getCurrentUser(); // Commented out or set to null if not needed for public view
$currentUser = isset($_SESSION['user_id']) ? getCurrentUser() : null;

// Page title
$pageTitle = 'YankesDokpol - Dashboard';

// Get statistics data
$totalPeserta = 0;
$layananStats = [];
$ageDistribution = [];
$dailyRegistrations = [];
$withPhone = 0;
$withResults = 0;
$todayCount = 0;

// Query to get total participants
$result = executeQuery("SELECT COUNT(*) as total FROM peserta");
if ($result && $row = $result->fetch_assoc()) {
    $totalPeserta = $row['total'];
}

// Query to get participants with phone numbers
$result = executeQuery("SELECT COUNT(*) as total FROM peserta WHERE nomor_hp IS NOT NULL AND nomor_hp != ''");
if ($result && $row = $result->fetch_assoc()) {
    $withPhone = $row['total'];
}

// Query to get participants with examination results
$result = executeQuery("SELECT COUNT(DISTINCT p.nik) as total FROM peserta p 
    INNER JOIN peserta_layanan pl ON p.nik = pl.nik 
    WHERE pl.hasil_pemeriksaan IS NOT NULL AND pl.hasil_pemeriksaan != ''");
if ($result && $row = $result->fetch_assoc()) {
    $withResults = $row['total'];
}

// Query to get today's registrations
$today = date('Y-m-d');
$result = executeQuery("SELECT COUNT(*) as total FROM peserta WHERE DATE(created_at) = '{$today}'");
if ($result && $row = $result->fetch_assoc()) {
    $todayCount = $row['total'];
}

// Query to get service statistics
$result = executeQuery("SELECT l.nama_layanan, COUNT(pl.id) as total 
    FROM layanan l 
    LEFT JOIN peserta_layanan pl ON l.id = pl.layanan_id 
    GROUP BY l.id 
    ORDER BY total DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $layananStats[$row['nama_layanan']] = (int)$row['total'];
    }
}

// Query to get age distribution
$result = executeQuery("SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 18 THEN 'Di bawah 18'
        WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
        WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
        WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
        ELSE 'Di atas 60'
    END as age_group,
    COUNT(*) as total
    FROM peserta
    WHERE tanggal_lahir IS NOT NULL
    GROUP BY age_group
    ORDER BY 
        CASE age_group
            WHEN 'Di bawah 18' THEN 1
            WHEN '18-30' THEN 2
            WHEN '31-45' THEN 3
            WHEN '46-60' THEN 4
            ELSE 5
        END");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ageDistribution[$row['age_group']] = (int)$row['total'];
    }
}

// Query to get daily registrations for the last 14 days
$result = executeQuery("SELECT DATE(created_at) as date, COUNT(*) as total 
    FROM peserta 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dailyRegistrations[$row['date']] = (int)$row['total'];
    }
}

// Set page content
$pageContent = 'content/dashboard_content.php';

// Include admin layout
include 'includes/admin_layout.php';

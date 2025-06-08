<?php
// API Visualisasi Dashboard
require_once '../config/database.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ambil parameter filter
$acara_id = isset($_GET['acara_id']) && $_GET['acara_id'] !== 'all' ? intval($_GET['acara_id']) : null;

// Siapkan kondisi WHERE untuk filter acara
$where_acara = $acara_id ? "AND pl.acara_id = $acara_id" : '';
$where_clause = $acara_id ? "WHERE pl.acara_id = $acara_id" : '';

// Debug: Tampilkan parameter yang diterima
error_log("acara_id: " . ($acara_id ?? 'null'));

// Distribusi layanan (pie)
$q1 = "SELECT 
    l.id,
    l.nama_layanan AS nama, 
    COUNT(pl.id) AS jumlah
FROM layanan l 
LEFT JOIN peserta_layanan pl ON l.id = pl.layanan_id 
    ".($acara_id ? "AND pl.acara_id = $acara_id" : "")."
GROUP BY l.id, l.nama_layanan 
ORDER BY jumlah DESC, l.nama_layanan";

// Debug: Tampilkan query yang dijalankan
error_log("Query layanan: " . $q1);

// Debug: Tampilkan data layanan yang ada
$debug_layanan = executeQuery("SELECT id, nama_layanan FROM layanan");
if ($debug_layanan) {
    error_log("Daftar layanan di database:");
    while ($row = $debug_layanan->fetch_assoc()) {
        error_log("- ID: " . $row['id'] . ", Nama: " . $row['nama_layanan']);
    }
}
$res1 = executeQuery($q1);
$layanan = [];
if ($res1) {
    while ($row = $res1->fetch_assoc()) {
        $layanan[] = $row;
    }
    $res1->free();
}

// Debug: Tampilkan data yang akan dikirim sebagai response
error_log("Data layanan yang akan dikirim: " . json_encode($layanan));

// Total peserta per acara (bar)
$q2 = "SELECT a.id, a.nama_acara AS nama, COUNT(pl.id) AS jumlah
FROM acara a
LEFT JOIN peserta_layanan pl ON a.id = pl.acara_id $where_acara
GROUP BY a.id, a.nama_acara 
ORDER BY jumlah DESC, a.nama_acara";
$res2 = executeQuery($q2);
$totalPeserta = [];
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $totalPeserta[] = $row;
    }
    $res2->free();
}

// Peserta per jenis layanan (bar)
$q3 = "SELECT l.id, l.nama_layanan AS nama, COUNT(pl.id) AS jumlah
FROM layanan l
LEFT JOIN peserta_layanan pl ON l.id = pl.layanan_id $where_acara
GROUP BY l.id, l.nama_layanan
ORDER BY jumlah DESC, l.nama_layanan";
$res3 = executeQuery($q3);
$pesertaPemeriksaan = [];
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $pesertaPemeriksaan[] = $row;
    }
    $res3->free();
}

// Distribusi usia (bar)
$q4 = "SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) < 18 THEN 'Di bawah 18'
        WHEN TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
        WHEN TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
        WHEN TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
        ELSE 'Di atas 60'
    END AS kelompok, 
    COUNT(pl.id) AS jumlah
FROM peserta_layanan pl
LEFT JOIN peserta p ON pl.nik = p.nik
WHERE p.tanggal_lahir IS NOT NULL $where_acara
GROUP BY kelompok 
ORDER BY 
    CASE kelompok
        WHEN 'Di bawah 18' THEN 1
        WHEN '18-30' THEN 2
        WHEN '31-45' THEN 3
        WHEN '46-60' THEN 4
        ELSE 5
    END";
$res4 = executeQuery($q4);
$distribusiUsia = [];
if ($res4) {
    while ($row = $res4->fetch_assoc()) {
        $distribusiUsia[] = $row;
    }
    $res4->free();
}

// Summary statistik
// Total peserta unik
$q5 = "SELECT COUNT(DISTINCT pl.nik) AS total FROM peserta_layanan pl $where_clause";
$res5 = executeQuery($q5);
$stats = ['totalPeserta' => 0, 'totalAcara' => 0, 'totalLayanan' => 0, 'avgUsia' => 0];
if ($res5 && $row = $res5->fetch_assoc()) $stats['totalPeserta'] = (int)$row['total'];
// Total acara
$q6 = "SELECT COUNT(DISTINCT pl.acara_id) AS total FROM peserta_layanan pl $where_clause";
$res6 = executeQuery($q6);
if ($res6 && $row = $res6->fetch_assoc()) $stats['totalAcara'] = (int)$row['total'];
// Total layanan
$q7 = "SELECT COUNT(DISTINCT pl.layanan_id) AS total FROM peserta_layanan pl $where_clause";
$res7 = executeQuery($q7);
if ($res7 && $row = $res7->fetch_assoc()) $stats['totalLayanan'] = (int)$row['total'];
// Rata-rata usia
$q8 = "SELECT AVG(TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE())) AS avg_usia
FROM peserta_layanan pl
JOIN peserta p ON pl.nik = p.nik
WHERE p.tanggal_lahir IS NOT NULL $where_acara";
$res8 = executeQuery($q8);
if ($res8 && $row = $res8->fetch_assoc()) $stats['avgUsia'] = round($row['avg_usia'],1);

// Data SATKER (Satuan Kerja)
$q_satker = "SELECT 
    s.nama_satker AS nama, 
    COUNT(pl.id) AS jumlah
FROM peserta_layanan pl
JOIN satker s ON pl.satker_id = s.id
WHERE s.is_active = 1";

// Tambahkan kondisi WHERE untuk acara jika ada
if ($acara_id) {
    $q_satker .= " AND pl.acara_id = $acara_id";
}

$q_satker .= "
GROUP BY s.id, s.nama_satker
HAVING COUNT(pl.id) > 0
ORDER BY jumlah DESC, s.nama_satker
LIMIT 10"; // Batasi 10 SATKER teratas

try {
    $res_satker = executeQuery($q_satker);
    $satker = [];
    if ($res_satker) {
        while ($row = $res_satker->fetch_assoc()) {
            $satker[] = $row;
        }
        $res_satker->free();
    }
} catch (Exception $e) {
    error_log("Error in SATKER query: " . $e->getMessage());
    $satker = [];
}

$response = [
    'layanan' => $layanan,
    'totalPeserta' => $totalPeserta,
    'pesertaPemeriksaan' => $pesertaPemeriksaan,
    'distribusiUsia' => $distribusiUsia,
    'stats' => $stats,
    'satker' => $satker
];

try {
    $json_response = json_encode($response);
    if ($json_response === false) {
        throw new Exception('JSON encode error: ' . json_last_error_msg());
    }
    echo $json_response;
} catch (Exception $e) {
    error_log("JSON encode error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan saat memproses data.']);
}

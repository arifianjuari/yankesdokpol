<?php
/**
 * Export Data Page
 * 
 * This page handles exporting participant data to Excel format
 * for the YankesDokpol application.
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
$pageTitle = 'YankesDokpol - Export Data';

// Check if the form was submitted for export
$isExporting = isset($_GET['start_date']) || isset($_GET['end_date']) || (isset($_GET['layanan_id']) && $_GET['layanan_id'] > 0);

// Get filter parameters from query string
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$layananId = isset($_GET['layanan_id']) ? (int)$_GET['layanan_id'] : 0;

// Get list of layanan for the filter dropdown
$layananQuery = "SELECT id, nama_layanan FROM layanan ORDER BY nama_layanan";
$layananResult = $conn->query($layananQuery);
$layananList = [];

if ($layananResult) {
    while ($row = $layananResult->fetch_assoc()) {
        $layananList[] = $row;
    }
}

// If not exporting, just show the form
if (!$isExporting) {
    // Set the page content to the export form
    $pageContent = __DIR__ . '/content/export_content.php';
    // Include the admin layout from the correct path
    include __DIR__ . '/includes/admin_layout.php';
    exit;
}

// If we're here, we're exporting data

// Check if PhpSpreadsheet is installed
if (!file_exists('vendor/autoload.php')) {
    // If PhpSpreadsheet is not installed, use a fallback method
    exportCSVFallback();
    exit;
}

// Include Composer autoloader
require 'vendor/autoload.php';

// PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Check if PhpSpreadsheet is actually available
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    // If not available, fall back to CSV export
    exportCSVFallback();
    exit;
}

// Build query based on filters
$query = "SELECT p.*, GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan_list 
         FROM peserta p 
         LEFT JOIN peserta_layanan pl ON p.nik = pl.nik 
         LEFT JOIN layanan l ON pl.layanan_id = l.id";

$whereConditions = [];
$params = [];
$types = '';

if (!empty($startDate)) {
    $whereConditions[] = "p.created_at >= ?";
    $params[] = $startDate . ' 00:00:00';
    $types .= 's';
}

if (!empty($endDate)) {
    $whereConditions[] = "p.created_at <= ?";
    $params[] = $endDate . ' 23:59:59';
    $types .= 's';
}

if ($layananId > 0) {
    $whereConditions[] = "pl.layanan_id = ?";
    $params[] = $layananId;
    $types .= 'i';
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " GROUP BY p.nik ORDER BY p.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Create new Spreadsheet object
try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data Peserta');
    
    // Set headers
    $headers = [
        'No', 'NIK', 'Nama', 'Alamat', 'Tanggal Lahir', 'Umur', 
        'Nomor HP', 'Layanan', 'Hasil Pemeriksaan', 'Tanggal Daftar'
    ];
    
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Style the header row
    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2EFDA']
        ]
    ];
    
    $sheet->getStyle('A1:' . --$col . '1')->applyFromArray($headerStyle);
    
    // Add data rows
    $row = 2;
    $no = 1;
    
    while ($data = $result->fetch_assoc()) {
        $age = calculateAge($data['tanggal_lahir']);
        
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, $data['nik']);
        $sheet->setCellValue('C' . $row, $data['nama']);
        $sheet->setCellValue('D' . $row, $data['alamat']);
        $sheet->setCellValue('E' . $row, formatDate($data['tanggal_lahir']));
        $sheet->setCellValue('F' . $row, $age);
        $sheet->setCellValue('G' . $row, $data['nomor_hp']);
        $sheet->setCellValue('H' . $row, $data['layanan_list'] ?? '');
        $sheet->setCellValue('I' . $row, $data['hasil_pemeriksaan']);
        $sheet->setCellValue('J' . $row, formatDate($data['created_at'], true));
        
        $row++;
        $no++;
    }
    
    // Auto size columns
    foreach (range('A', 'J') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    // Set borders for all data
    $styleArray = [
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
    
    $sheet->getStyle('A1:J' . ($row - 1))->applyFromArray($styleArray);
    
    // Create writer and output file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    $filename = 'Data_Peserta_YankesDokpol_' . date('Y-m-d_H-i-s') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    // If PhpSpreadsheet fails, fall back to CSV
    error_log("PhpSpreadsheet error: " . $e->getMessage());
    exportCSVFallback($result);
}

/**
 * Fallback function to export data as CSV if PhpSpreadsheet is not available
 */
function exportCSVFallback($result = null) {
    global $conn;
    
    // If result is not passed, execute query
    if ($result === null) {
        $query = "SELECT p.*, GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan_list 
                 FROM peserta p 
                 LEFT JOIN peserta_layanan pl ON p.nik = pl.nik 
                 LEFT JOIN layanan l ON pl.layanan_id = l.id 
                 GROUP BY p.nik ORDER BY p.created_at DESC";
        $result = $conn->query($query);
    }
    
    // Set headers for CSV download
    $filename = 'Data_Peserta_YankesDokpol_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    // Create file pointer connected to output
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, "\xEF\xBB\xBF");
    
    // Add headers
    fputcsv($output, ['No', 'NIK', 'Nama', 'Alamat', 'Tanggal Lahir', 'Umur', 
                     'Nomor HP', 'Layanan', 'Hasil Pemeriksaan', 'Tanggal Daftar']);
    
    // Add data rows
    $no = 1;
    while ($data = $result->fetch_assoc()) {
        $age = calculateAge($data['tanggal_lahir']);
        
        fputcsv($output, [
            $no,
            $data['nik'],
            $data['nama'],
            $data['alamat'],
            formatDate($data['tanggal_lahir']),
            $age,
            $data['nomor_hp'],
            $data['layanan_list'] ?? '',
            $data['hasil_pemeriksaan'],
            formatDate($data['created_at'], true)
        ]);
        
        $no++;
    }
    
    fclose($output);
    exit;
}

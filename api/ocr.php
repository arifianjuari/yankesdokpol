<?php
/**
 * OCR API
 * 
 * This file handles OCR processing for KTP images.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Enable error handling that won't break JSON output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set up error handler to convert PHP errors to JSON responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log error to file
    error_log("OCR API Error [{$errno}]: {$errstr} in {$errfile}:{$errline}");
    
    // Return JSON error
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred',
        'debug' => "[{$errno}] {$errstr}"
    ]);
    exit;
});

// Handle uncaught exceptions
set_exception_handler(function($e) {
    // Log exception to file
    error_log("OCR API Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Return JSON error
    echo json_encode([
        'status' => 'error',
        'message' => 'Server exception occurred',
        'debug' => $e->getMessage()
    ]);
    exit;
});

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/validation.php';
require_once '../includes/ocr.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['ktp_image']) || $_FILES['ktp_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
$fileType = $_FILES['ktp_image']['type'];

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG and PNG are allowed']);
    exit;
}

// Cek apakah NIK diberikan dan file sudah diupload sebelumnya
if (isset($_POST['nik']) && !empty($_POST['nik'])) {
    // Mulai session jika belum dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Cek apakah ada file KTP yang sudah diupload untuk NIK ini
    $nik = $_POST['nik'];
    $ktpPattern = "../assets/uploads/ktp/ktp_{$nik}_*.{jpg,jpeg,png}";
    $existingFiles = glob($ktpPattern, GLOB_BRACE);
    
    // Hapus file KTP lama jika ada lebih dari satu file
    if (count($existingFiles) > 1) {
        // Urutkan file berdasarkan waktu modifikasi (terbaru dulu)
        usort($existingFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Simpan file terbaru
        $newestFile = $existingFiles[0];
        
        // Hapus file lama (kecuali yang terbaru)
        for ($i = 1; $i < count($existingFiles); $i++) {
            $oldFile = $existingFiles[$i];
            if (file_exists($oldFile)) {
                if (unlink($oldFile)) {
                    error_log("Deleted old KTP file during OCR: {$oldFile}");
                } else {
                    error_log("Failed to delete old KTP file during OCR: {$oldFile}");
                }
                
                // Cek dan hapus file optimized jika ada
                $pathInfo = pathinfo($oldFile);
                $optimizedFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.jpg';
                if (file_exists($optimizedFilePath)) {
                    if (unlink($optimizedFilePath)) {
                        error_log("Deleted old optimized KTP file during OCR: {$optimizedFilePath}");
                    }
                }
            }
        }
        
        // Gunakan file terbaru
        $filePath = $newestFile;
        error_log("OCR API using newest file for NIK {$nik}: {$filePath}");
    } else if (!empty($existingFiles)) {
        // Gunakan file yang sudah ada (hanya ada satu)
        $filePath = $existingFiles[0];
        error_log("OCR API using existing file for NIK {$nik}: {$filePath}");
    } else {
        // Jika tidak ada file yang cocok, simpan file yang diupload secara permanen
        $uploadDir = '../assets/uploads/ktp/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Buat nama file dengan format ktp_NIK_timestamp.ext
        $ext = pathinfo($_FILES['ktp_image']['name'], PATHINFO_EXTENSION);
        $filename = 'ktp_' . $nik . '_' . uniqid() . '.' . $ext;
        $permanentPath = $uploadDir . $filename;
        
        // Pindahkan file ke lokasi permanen
        if (move_uploaded_file($_FILES['ktp_image']['tmp_name'], $permanentPath)) {
            $filePath = $permanentPath;
            error_log("OCR API saved permanent file for NIK {$nik}: {$permanentPath}");
        } else {
            // Jika gagal menyimpan, gunakan file temporary
            $tempFilePath = $_FILES['ktp_image']['tmp_name'];
            $filePath = $tempFilePath;
            error_log("OCR API failed to save permanent file, using temporary file for NIK {$nik}: {$tempFilePath}");
        }
    }
} else {
    // Tidak ada NIK, gunakan file temporary
    $tempFilePath = $_FILES['ktp_image']['tmp_name'];
    $filePath = $tempFilePath;
    error_log("OCR API using temporary file: {$tempFilePath}");
}

// Pastikan file ada
if (!file_exists($filePath)) {
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan']);
    exit;
}

// Log API call
error_log("OCR API called with file: {$_FILES['ktp_image']['name']}, size: {$_FILES['ktp_image']['size']}");

// Process image with OCR
try {
    // Prepare debug log
    $debugLogFile = '../includes/ocr_debug.txt';
    file_put_contents($debugLogFile, "=== API CALL START: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    
    // Log file path sebelum preprocessing
    file_put_contents($debugLogFile, "Original file path: {$filePath}\n", FILE_APPEND);
    
    // Simpan path file sementara untuk dihapus nanti
    $tempFiles = [];
    
    // Preprocess image for better OCR results
    $processedImagePath = preprocessImage($filePath);
    if ($processedImagePath !== $filePath) {
        $tempFiles[] = $processedImagePath;
    }
    file_put_contents($debugLogFile, "Preprocessed image: {$processedImagePath}\n", FILE_APPEND);
    
    // Extract data using OCR
    $ocrData = processKTPWithOCR($processedImagePath);
    
    // Log OCR results
    file_put_contents($debugLogFile, "OCR Results: " . json_encode($ocrData) . "\n", FILE_APPEND);
    
    // Check for errors in OCR data
    if (!empty($ocrData['errors'])) {
        file_put_contents($debugLogFile, "OCR Validation Errors: " . json_encode($ocrData['errors']) . "\n", FILE_APPEND);
        
        // Still return the data, but mark status based on if any critical fields are missing
        $criticalFieldsMissing = isset($ocrData['errors']['nik']) || isset($ocrData['errors']['nama']);
        
        echo json_encode([
            'status' => $criticalFieldsMissing ? 'warning' : 'success',
            'message' => $criticalFieldsMissing ? 'OCR completed with some missing information' : 'OCR processing completed',
            'data' => $ocrData
        ]);
    } else {
        // Return successful OCR results
        echo json_encode([
            'status' => 'success',
            'message' => 'OCR processing completed successfully',
            'data' => $ocrData
        ]);
    }
    
    // Hapus file-file sementara setelah proses OCR selesai
    foreach ($tempFiles as $tempFile) {
        if (file_exists($tempFile) && is_file($tempFile)) {
            unlink($tempFile);
            file_put_contents($debugLogFile, "Deleted temporary file: {$tempFile}\n", FILE_APPEND);
        }
    }
} catch (Exception $e) {
    // Log error details
    error_log("OCR processing exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    file_put_contents('../includes/ocr_debug.txt', "OCR ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    // Hapus file-file sementara jika terjadi error
    if (!empty($tempFiles)) {
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile) && is_file($tempFile)) {
                unlink($tempFile);
                file_put_contents('../includes/ocr_debug.txt', "Deleted temporary file on error: {$tempFile}\n", FILE_APPEND);
            }
        }
    }
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Proses OCR gagal. Coba lagi atau gunakan input manual.',
        'debug' => $e->getMessage()
    ]);
}

<?php
// Atur batas waktu eksekusi yang lebih tinggi
ini_set('max_execution_time', 300); // 5 menit

/**
 * OCR Functions File
 * 
 * This file contains functions related to OCR processing for KTP images.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

/**
 * Process KTP image with OCR
 * 
 * @param string $imagePath Path to the KTP image
 * @return array Extracted data (nik, nama, alamat, tanggal_lahir)
 */
function processKTPWithOCR($imagePath) {
    // Reset log file untuk membuat log baru yang bersih
    file_put_contents(__DIR__ . '/ocr_debug.txt', "=== PROSES OCR KTP BARU: " . date('Y-m-d H:i:s') . " ===\n\n");
    file_put_contents(__DIR__ . '/ocr_debug.txt', "IMAGE PATH: {$imagePath}\n", FILE_APPEND);
    
    // Dapatkan teks OCR dari API
    $ocrText = ocrSpaceAPI($imagePath);

    // Simpan hasil mentah OCR ke file untuk analisis
    file_put_contents(__DIR__ . '/ocr_debug.txt', "=== OCR TEXT MENTAH ===\n{$ocrText}\n\n", FILE_APPEND);
    
    // Extract data from OCR text
    $nik = extractNIK($ocrText);
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Hasil ekstrak NIK: {$nik}\n", FILE_APPEND);
    
    $nama = extractName($ocrText);
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Hasil ekstrak Nama: {$nama}\n", FILE_APPEND);
    
    $alamat = extractAddress($ocrText);
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Hasil ekstrak Alamat: {$alamat}\n", FILE_APPEND);
    
    $tanggalLahir = extractBirthDate($ocrText);
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Hasil ekstrak Tanggal Lahir: {$tanggalLahir}\n\n", FILE_APPEND);
    
    // Return extracted data
    $data = [
        'nik' => $nik,
        'nama' => $nama,
        'alamat' => $alamat,
        'tanggal_lahir' => $tanggalLahir
    ];

    // Validate OCR results
    return validateOCRResults($data);
}

/**
 * Check if Tesseract OCR is installed
 * 
 * @return bool True if installed, false otherwise
 */
function isTesseractInstalled() {
    $output = [];
    $returnVar = 0;
    exec('which tesseract 2>&1', $output, $returnVar);
    return $returnVar === 0;
}

/**
 * Run Tesseract OCR on an image
 * 
 * @param string $imagePath Path to the image
 * @return string OCR extracted text
 */
function runTesseractOCR($imagePath) {
    $output = [];
    $returnVar = 0;
    
    // Run Tesseract with Indonesian language
    exec("tesseract {$imagePath} stdout -l ind 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        // If error occurred, log it and return empty string
        error_log("Tesseract OCR error: " . implode("\n", $output));
        return '';
    }
    
    return implode("\n", $output);
}

/**
 * Preprocess image for better OCR results
 * 
 * @param string $imagePath Path to the image file
 * @return string Path to the preprocessed image
 */
function preprocessImage($imagePath) {
    // Buat nama file untuk hasil preprocessing
    $pathInfo = pathinfo($imagePath);
    $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.jpg';
    
    // Lakukan preprocessing dengan GD library
    if (extension_loaded('gd')) {
        // Load the image based on file extension
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $source = null;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source = @imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $source = @imagecreatefrompng($imagePath);
                break;
            case 'gif':
                $source = @imagecreatefromgif($imagePath);
                break;
            default:
                return $imagePath; // Unsupported format
        }
        
        if (!$source) {
            return $imagePath; // Failed to load image
        }
        
        // Get original dimensions
        $width = imagesx($source);
        $height = imagesy($source);
        
        // Resize large images to reduce file size
        if ($width > 1000 || $height > 1000) {
            // Calculate new dimensions while maintaining aspect ratio
            if ($width > $height) {
                $newWidth = 1000;
                $newHeight = intval($height * (1000 / $width));
            } else {
                $newHeight = 1000;
                $newWidth = intval($width * (1000 / $height));
            }
            
            // Create a new image with the new dimensions
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }
        
        // Apply preprocessing filters
        // Convert to grayscale
        imagefilter($source, IMG_FILTER_GRAYSCALE);
        
        // Adjust contrast
        imagefilter($source, IMG_FILTER_CONTRAST, -10);
        
        // Adjust brightness
        imagefilter($source, IMG_FILTER_BRIGHTNESS, 10);
        
        // Save the processed image with optimized quality (75% for better compression)
        imagejpeg($source, $outputPath, 75);
        imagedestroy($source);
        
        // Jika file hasil preprocessing berhasil dibuat dan lebih kecil dari file asli
        if (file_exists($outputPath) && filesize($outputPath) < filesize($imagePath)) {
            error_log("Created optimized image: {$outputPath} (" . filesize($outputPath) . " bytes vs original " . filesize($imagePath) . " bytes)");
            
            // Hapus file asli jika file preprocessing berhasil dibuat
            if (unlink($imagePath)) {
                error_log("Deleted original file: {$imagePath}");
            } else {
                error_log("Failed to delete original file: {$imagePath}");
            }
            
            return $outputPath;
        } else {
            // Jika file preprocessing lebih besar atau gagal dibuat, hapus file preprocessing dan gunakan file asli
            if (file_exists($outputPath)) {
                unlink($outputPath);
                error_log("Deleted larger optimized file: {$outputPath}");
            }
            return $imagePath;
        }
    }
    
    // Jika tidak ada library GD, kembalikan path file asli
    return $imagePath;
}

/**
 * Extract NIK from OCR result
 * 
 * @param string $ocrText OCR extracted text
 * @return string|null Extracted NIK or null if not found
 */
function extractNIK($ocrText) {
    // Pattern for NIK: 16 consecutive digits
    if (preg_match('/\b(\d{16})\b/', $ocrText, $matches)) {
        return $matches[1];
    }
    // Alternative pattern: NIK label followed by digits (toleran spasi/noise)
    if (preg_match('/N[I1][Kk]\s*[:\-]?\s*([0-9Il]{16})/i', $ocrText, $matches)) {
        // Ganti I/l dengan 1 jika OCR keliru
        return preg_replace('/[Il]/', '1', $matches[1]);
    }
    // Pattern fallback: 16 digit di awal baris
    if (preg_match('/^\s*([0-9Il]{16})/m', $ocrText, $matches)) {
        return preg_replace('/[Il]/', '1', $matches[1]);
    }
    return null;
}

/**
 * Extract name from OCR result
 * 
 * @param string $ocrText OCR extracted text
 * @return string|null Extracted name or null if not found
 */
function extractName($ocrText) {
    // Regex khusus format KTP: Nama : XXX kemudian diikuti Tempat/Tgl
    if (preg_match('/Nama\s*[:\-]?\s*([A-Z\s\.]+)(?=\s*Tempat|\s*Jenis)/i', $ocrText, $matches)) {
        return trim($matches[1]);
    }
    
    // Pola lain untuk "Nama :" yang diikuti teks kapital sampai baris baru atau karakter non-alfabet
    if (preg_match('/Nama\s*[:\-]?\s*([A-Z\s\.]+?)(?=[^A-Z\s\.]|$)/i', $ocrText, $matches)) {
        return trim($matches[1]);
    }
    
    // Mencari teks kapital setelah NIK dengan panjang 5-50 karakter
    if (preg_match('/\b\d{16}\b.*?[\n\r]+([A-Z][A-Z\s\.]{4,50})/i', $ocrText, $matches)) {
        return trim($matches[1]);
    }
    
    // Jika gagal, coba mencari teks uppercase yang panjang
    if (preg_match('/\b([A-Z][A-Z\s\.]{4,30})\b/m', $ocrText, $matches)) {
        return trim($matches[1]);
    }
    
    return null;
}

/**
 * Extract address from OCR result
 * 
 * @param string $ocrText OCR extracted text
 * @return string|null Extracted address or null if not found
 */
function extractAddress($ocrText) {
    // Pattern untuk label Alamat (toleran noise/spasi)
    if (preg_match('/Alamat\s*[:\-]?\s*(.+?)(?=RT\/?RW|Kel|Kec|Agama|Status|Pekerjaan|Kewarganegaraan|Berlaku|\n)/is', $ocrText, $matches)) {
        return trim($matches[1]);
    }
    // Alternatif: baris setelah label Alamat
    if (preg_match('/Alamat\s*[:\-]?\s*\n(.+)/i', $ocrText, $matches)) {
        return trim($matches[1]);
    }
    // Alternatif: cari baris yang mengandung "JL"/"JALAN"/dst
    if (preg_match('/\b(JL|JALAN|DSN|DUSUN|DESA)[\.:\- ]+([^\n]+)/i', $ocrText, $matches)) {
        return trim($matches[2]);
    }
    return null;
}

/**
 * Extract birth date from OCR result
 * 
 * @param string $ocrText OCR extracted text
 * @return string|null Extracted birth date (Y-m-d) or null if not found
 */
function extractBirthDate($ocrText) {
    // Log untuk debugging
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Mencoba ekstrak tanggal lahir dari OCR text...\n", FILE_APPEND);
    
    // Format KTP: "Tempat/Tgl Lahir : KOTA, DD-MM-YYYY"
    if (preg_match('/Tempat\s*\/\s*Tgl\s+Lahir\s*[:\-]?\s*[^,]+,\s*(\d{1,2})\s*[-\/]\s*(\d{1,2})\s*[-\/]\s*(\d{4})/i', $ocrText, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        $birthDate = $year . '-' . $month . '-' . $day;
        file_put_contents(__DIR__ . '/ocr_debug.txt', "Format KTP ditemukan: {$birthDate}\n", FILE_APPEND);
        return $birthDate;
    }
    
    // Format umum: DD-MM-YYYY yang mengikuti kata "Lahir"
    if (preg_match('/Lahir\s*[:.]?\s*(\d{1,2})\s*[-.\/]\s*(\d{1,2})\s*[-.\/]\s*(\d{4})/i', $ocrText, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        $birthDate = $year . '-' . $month . '-' . $day;
        file_put_contents(__DIR__ . '/ocr_debug.txt', "Format tanggal umum ditemukan: {$birthDate}\n", FILE_APPEND);
        return $birthDate;
    }
    
    // Cari format tanggal DD-MM-YYYY di manapun dalam teks
    if (preg_match('/(\d{1,2})\s*[-.\/]\s*(\d{1,2})\s*[-.\/]\s*(\d{4})/i', $ocrText, $matches)) {
        $day = intval($matches[1]);
        $month = intval($matches[2]);
        $year = intval($matches[3]);
        
        // Validasi tanggal masuk akal (menghindari false positive)
        if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12 && $year >= 1940 && $year <= 2010) {
            $birthDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            file_put_contents(__DIR__ . '/ocr_debug.txt', "Tanggal ditemukan secara umum: {$birthDate}\n", FILE_APPEND);
            return $birthDate;
        }
    }
    
    // Format tanggal dengan teks bulan (e.g., "17 Juli 1984")
    $months = [
        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'
    ];
    
    if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/i', $ocrText, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $monthText = strtolower($matches[2]);
        $year = $matches[3];
        
        // Coba temukan bulan berdasarkan nama
        foreach ($months as $monthName => $monthNum) {
            if (strpos($monthText, $monthName) === 0 || strpos($monthName, $monthText) === 0) {
                $birthDate = $year . '-' . $monthNum . '-' . $day;
                file_put_contents(__DIR__ . '/ocr_debug.txt', "Format tanggal teks bulan ditemukan: {$birthDate}\n", FILE_APPEND);
                return $birthDate;
            }
        }
    }
    
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Tidak berhasil menemukan format tanggal lahir\n", FILE_APPEND);
    return null;
}

// Fungsi validateDateFormat() sudah tersedia di validation.php

/**
 * Validate OCR results
 * 
 * @param array $ocrData OCR extracted data
 * @return array Validated data with error flags
 */
function validateOCRResults($ocrData) {
    $errors = [];
    
    // Validate NIK
    if (empty($ocrData['nik']) || !preg_match('/^\d{16}$/', $ocrData['nik'])) {
        $errors['nik'] = 'NIK tidak valid atau tidak ditemukan';
        $ocrData['nik'] = '';
    }
    
    // Validate name
    if (empty($ocrData['nama'])) {
        $errors['nama'] = 'Nama tidak ditemukan';
        $ocrData['nama'] = '';
    }
    
    // Validate address
    if (empty($ocrData['alamat'])) {
        $errors['alamat'] = 'Alamat tidak ditemukan';
        $ocrData['alamat'] = '';
    }
    
    // Validate birth date
    if (empty($ocrData['tanggal_lahir']) || !validateDateFormat($ocrData['tanggal_lahir'])) {
        $errors['tanggal_lahir'] = 'Tanggal lahir tidak valid atau tidak ditemukan';
        $ocrData['tanggal_lahir'] = '';
    }
    
    // Add errors to data
    $ocrData['errors'] = $errors;
    $ocrData['success'] = empty($errors);
    
    return $ocrData;
}

// Tambahkan ini di bagian atas file (atau awal fungsi) untuk melihat semua error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log lokasi error_log PHP
file_put_contents(__DIR__ . '/ocr_debug.txt', "PHP Error Log Path: " . ini_get('error_log') . "\n\n", FILE_APPEND);

/**
 * Kompres gambar untuk upload ke OCR API
 * 
 * @param string $imagePath Path to the image file
 * @return string Path to the compressed image
 */
function compressImage($imagePath) {
    // Buat nama file baru untuk gambar yang dikompres
    $pathInfo = pathinfo($imagePath);
    $compressedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_compressed.jpg'; // Selalu simpan sebagai JPG
    
    // Buka gambar berdasarkan tipe
    $image = null;
    $mime = mime_content_type($imagePath);
    
    if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $image = imagecreatefromjpeg($imagePath);
    } elseif ($mime == 'image/png') {
        $image = imagecreatefrompng($imagePath);
        // Untuk PNG, kita perlu menangani transparansi
        $width = imagesx($image);
        $height = imagesy($image);
        $newImage = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefilledrectangle($newImage, 0, 0, $width, $height, $white);
        imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);
        $image = $newImage;
    } else {
        // Jika bukan JPEG atau PNG, kembalikan path asli
        return $imagePath;
    }
    
    // Dapatkan dimensi gambar asli
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Resize gambar ke ukuran yang lebih kecil untuk OCR (800px max)
    if ($width > 800 || $height > 800) {
        // Hitung rasio aspek
        $ratio = $width / $height;
        
        if ($width > $height) {
            $newWidth = 800;
            $newHeight = round(800 / $ratio);
        } else {
            $newHeight = 800;
            $newWidth = round(800 * $ratio);
        }
        
        // Buat gambar baru dengan ukuran yang lebih kecil
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Hapus gambar asli dari memori
        imagedestroy($image);
        $image = $resized;
    }
    
    // Tingkatkan kontras untuk OCR yang lebih baik
    imagefilter($image, IMG_FILTER_CONTRAST, -10);
    
    // Simpan dengan kualitas lebih rendah (50%)
    imagejpeg($image, $compressedPath, 50);
    imagedestroy($image);
    
    // Log ukuran file hasil kompresi
    $newSize = filesize($compressedPath);
    file_put_contents(__DIR__ . '/ocr_debug.txt', "Ukuran file setelah kompresi: {$newSize} bytes\n", FILE_APPEND);
    
    return $compressedPath;
}

function ocrSpaceAPI($imagePath) {
    try {
        // Logging awal fungsi
        file_put_contents(__DIR__ . '/ocr_debug.txt', "\n\nOCR API CALL START: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
        $apiKey = 'K84109371988957'; // API key dari user
        $url = 'https://api.ocr.space/parse/image';
        $postFields = [
            'apikey' => $apiKey,
            'language' => 'eng', // gunakan 'eng' karena 'ind' tidak didukung
            'isOverlayRequired' => 'false', // kirim sebagai string 'false' bukan boolean
            'OCREngine' => 2, // engine 2 lebih baik untuk KTP
            'scale' => 'true', // aktifkan scaling untuk gambar
            'detectOrientation' => 'true', // deteksi orientasi otomatis
            'filetype' => 'jpg' // pastikan filetype dikenali dengan benar
        ];
        
        // Validasi file
        if (!file_exists($imagePath)) {
            file_put_contents(__DIR__ . '/ocr_debug.txt', "ERROR: File tidak ditemukan di path: {$imagePath}\n", FILE_APPEND);
            return "";
        }
        
        if (!is_readable($imagePath)) {
            file_put_contents(__DIR__ . '/ocr_debug.txt', "ERROR: File tidak bisa dibaca: {$imagePath}\n", FILE_APPEND);
            return "";
        }
        
        // Log file info
        $filesize = filesize($imagePath);
        $mimetype = mime_content_type($imagePath);
        file_put_contents(__DIR__ . '/ocr_debug.txt', "FILE INFO: Size={$filesize}bytes, Type={$mimetype}\n", FILE_APPEND);
        
        // Selalu kompres gambar untuk OCR
        $compressedFilePath = compressImage($imagePath);
        $newSize = filesize($compressedFilePath);
        file_put_contents(__DIR__ . '/ocr_debug.txt', "Gambar dikompres. Size baru={$newSize}bytes\n", FILE_APPEND);
        
        // Gunakan base64 image daripada file upload langsung
        try {
            // Baca file gambar ke base64 string
            $imageData = base64_encode(file_get_contents($compressedFilePath));
            file_put_contents(__DIR__ . '/ocr_debug.txt', "Base64 encoding berhasil, panjang: " . strlen($imageData) . " karakter\n", FILE_APPEND);
            
            // Set field base64Image pada postFields
            $postFields['base64Image'] = 'data:image/jpeg;base64,' . $imageData;
            file_put_contents(__DIR__ . '/ocr_debug.txt', "Data base64 siap dikirim\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/ocr_debug.txt', "ERROR Base64: " . $e->getMessage() . "\n", FILE_APPEND);
            return "";
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
        curl_setopt($ch, CURLOPT_TIMEOUT, 180); // Set timeout 180 detik (3 menit)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // Set connection timeout 60 detik
        
        // Aktifkan verbose mode untuk debug lengkap
        $verbose = fopen(__DIR__ . '/curl_verbose.txt', 'w+');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        file_put_contents(__DIR__ . '/ocr_debug.txt', "Mengirim request ke OCR.space...\n", FILE_APPEND);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        // Log semua informasi penting termasuk memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        file_put_contents(__DIR__ . '/ocr_debug.txt', "Memory Usage: {$memoryUsage} bytes, Memory Limit: {$memoryLimit}\n", FILE_APPEND);
        
        curl_close($ch);
        
        file_put_contents(__DIR__ . '/ocr_debug.txt', "HTTP Status: {$httpCode}\nCURL ERROR: {$curlError}\n", FILE_APPEND);
        // Simpan response dan keluar untuk debug
        file_put_contents(__DIR__ . '/ocr_debug.txt', "RAW RESPONSE (first 500 chars):\n" . substr($result, 0, 500) . "\n", FILE_APPEND);
        
        // Mencoba decode JSON
        try {
            $resultData = json_decode($result, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                file_put_contents(__DIR__ . '/ocr_debug.txt', "ERROR JSON PARSE: " . json_last_error_msg() . "\n", FILE_APPEND);
                return "";
            }
            
            // Log hasil parsing JSON
            file_put_contents(__DIR__ . '/ocr_debug.txt', "JSON Parse success. Structure: " . print_r(array_keys($resultData), true) . "\n", FILE_APPEND);
            
            // Cek apakah ada error dari API
            if (isset($resultData['IsErroredOnProcessing']) && $resultData['IsErroredOnProcessing'] === true) {
                $errorMsg = "API Error: ";
                if (isset($resultData['ErrorMessage']) && is_array($resultData['ErrorMessage'])) {
                    $errorMsg .= implode(", ", $resultData['ErrorMessage']);
                } elseif (isset($resultData['ErrorMessage'])) {
                    $errorMsg .= $resultData['ErrorMessage'];
                }
                
                file_put_contents(__DIR__ . '/ocr_debug.txt', "{$errorMsg}\n", FILE_APPEND);
                
                // Jika error adalah timeout, coba gunakan Tesseract lokal jika tersedia
                if (strpos($errorMsg, 'Timed out') !== false && function_exists('isTesseractInstalled') && isTesseractInstalled()) {
                    file_put_contents(__DIR__ . '/ocr_debug.txt', "Mencoba menggunakan Tesseract lokal sebagai fallback...\n", FILE_APPEND);
                    return runTesseractOCR($imagePath);
                }
                
                return "";
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/ocr_debug.txt', "Exception in JSON processing: " . $e->getMessage() . "\n", FILE_APPEND);
            return "";
        }
        
        if (!isset($resultData['ParsedResults'][0]['ParsedText'])) {
            file_put_contents(__DIR__ . '/ocr_debug.txt', "ERROR: No parsed text in response\n", FILE_APPEND);
            return "";
        }
        
        $parsedText = $resultData['ParsedResults'][0]['ParsedText'];
        file_put_contents(__DIR__ . '/ocr_debug.txt', "PARSED TEXT:\n{$parsedText}\n", FILE_APPEND);
        
        return $parsedText;
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/ocr_debug.txt', "ERROR EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
        return "";
    }
}
?>

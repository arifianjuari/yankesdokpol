<?php
// Script diagnostik untuk OCR.space API
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aktifkan output buffer agar langsung terlihat di browser
ob_implicit_flush(true);
ob_end_flush();

echo "<pre>\n";
echo "=== OCR.space API Test ===\n\n";

// Gunakan gambar kecil untuk tes
$sampleImage = __DIR__ . "/assets/logo.png"; 
if (!file_exists($sampleImage)) {
    echo "Gambar test tidak ditemukan. Menggunakan gambar KTP yang sudah dikompresi...\n";
    // Cari gambar KTP yang sudah diupload
    $ktpFiles = glob(__DIR__ . "/assets/uploads/ktp/*_processed.jpg");
    if (empty($ktpFiles)) {
        die("ERROR: Tidak ada file gambar untuk ditest!\n");
    }
    $sampleImage = $ktpFiles[0];
}

echo "Menggunakan gambar: " . $sampleImage . "\n";
echo "Ukuran file: " . filesize($sampleImage) . " bytes\n\n";

// Resize gambar menjadi sangat kecil
echo "Kompres gambar untuk test...\n";
$compressedImage = $sampleImage . '_test.jpg';
$image = imagecreatefromjpeg($sampleImage);
$width = imagesx($image);
$height = imagesy($image);

// Resize ke 400px width (sangat kecil)
$newWidth = 400;
$newHeight = $height * ($newWidth / $width);
$tmpImage = imagecreatetruecolor($newWidth, $newHeight);
imagecopyresampled($tmpImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
imagejpeg($tmpImage, $compressedImage, 60);
imagedestroy($image);
imagedestroy($tmpImage);

echo "Gambar dikompresi: " . filesize($compressedImage) . " bytes\n\n";

// Setup API
echo "Setup API OCR.space...\n";
$apiKey = 'K84109371988957';
$url = 'https://api.ocr.space/parse/image';

// Base64 encode gambar kecil
echo "Encoding gambar ke base64...\n";
$base64Image = base64_encode(file_get_contents($compressedImage));
echo "Panjang base64: " . strlen($base64Image) . " karakter\n\n";

// Kirim request dengan timeout yang pendek
echo "Mengirim request ke OCR.space (timeout 10 detik)...\n";
$postFields = [
    'apikey' => $apiKey,
    'language' => 'eng',  // Bahasa Indonesia tidak didukung, gunakan English
    'isOverlayRequired' => 'false', // harus string 'false' bukan boolean
    'OCREngine' => 2,
    'base64Image' => 'data:image/jpeg;base64,' . $base64Image
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$start = microtime(true);
$result = curl_exec($ch);
$time = microtime(true) - $start;

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Response time: " . round($time, 2) . " seconds\n";
echo "HTTP Status: {$httpCode}\n";
echo "CURL ERROR: {$curlError}\n\n";

// Coba parse JSON
echo "Mencoba parse JSON response...\n";
$json = json_decode($result, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: JSON parse error: " . json_last_error_msg() . "\n\n";
    echo "RAW RESPONSE (first 500 chars):\n" . substr($result, 0, 500) . "\n";
} else {
    echo "JSON parsed successfully!\n\n";
    
    // Cek status OCR dan tampilkan seluruh response untuk debug
    echo "STRUKTUR RESPONSE JSON LENGKAP:\n";
    print_r($json);
    
    echo "\n\nCEK STATUS OCR:\n";
    
    if (isset($json['IsErroredOnProcessing']) && $json['IsErroredOnProcessing'] === true) {
        echo "ERROR dari OCR.space:\n";
        if (is_array($json['ErrorMessage'])) {
            print_r($json['ErrorMessage']);
        } else {
            echo $json['ErrorMessage'] . "\n";
        }
    } elseif (isset($json['ParsedResults'][0]['ParsedText'])) {
        $text = $json['ParsedResults'][0]['ParsedText'];
        echo "TEXT HASIL OCR (" . strlen($text) . " karakter):\n\n";
        echo substr($text, 0, 500) . "\n";
    }
}

echo "\n=== Test Selesai ===\n";
?>

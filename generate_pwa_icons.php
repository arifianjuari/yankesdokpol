<?php
/**
 * Generate PWA Icons
 * 
 * This script generates placeholder icons for the PWA in various sizes
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Define icon sizes needed
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Create icons directory if it doesn't exist
$iconsDir = __DIR__ . '/assets/img/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

// Background color (green)
$bgColor = [46, 125, 50]; // #2e7d32

// Generate icons for each size
foreach ($sizes as $size) {
    // Create image
    $image = imagecreatetruecolor($size, $size);
    
    // Set background color
    $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    imagefill($image, 0, 0, $bg);
    
    // Set text color (white)
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Add text
    $fontSize = max(1, floor($size / 10));
    $text = "YDK";
    
    // Calculate text position
    $textX = $size / 2;
    $textY = $size / 2 - $fontSize;
    
    // Add text to image
    imagestring($image, $fontSize, $textX - (strlen($text) * $fontSize / 2), $textY, $text, $textColor);
    
    // Add second line of text
    $text2 = "Dokpol";
    $fontSize2 = max(1, floor($size / 15));
    imagestring($image, $fontSize2, $textX - (strlen($text2) * $fontSize2 / 2), $textY + $fontSize * 2, $text2, $textColor);
    
    // Save the image
    $filename = $iconsDir . "/icon-{$size}x{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);
    
    echo "Generated icon: $filename\n";
}

echo "All PWA icons have been generated successfully!\n";
?>

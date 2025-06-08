<?php

/**
 * General Functions File
 * 
 * This file contains general utility functions used throughout the YankesDokpol application.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

/**
 * Check if user is logged in, redirect to login page if not
 * 
 * @return void
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Anda harus login untuk mengakses halaman ini';
        header('Location: login.php');
        exit();
    }
}

/**
 * Check if user has admin role, redirect to dashboard if not
 * 
 * @return void
 */
/**
 * Check if current user has admin role
 * Redirects to dashboard if not admin
 * 
 * @return void
 */
function checkAdminRole() {
    require_once __DIR__ . '/auth.php';
    if (!hasRole('admin')) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini';
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Standardize photo path handling for foto_kegiatan and other images
 * 
 * @param string $photoPath The photo path stored in database
 * @param string $baseDir The base directory prefix (default: 'assets/uploads/dokumentasi/')
 * @return string The standardized path for display or access
 */
function getPhotoPath($photoPath, $baseDir = 'assets/uploads/dokumentasi/') {
    if (empty($photoPath)) {
        return '';
    }
    
    // Check if the path already includes the directory prefix
    if (strpos($photoPath, $baseDir) === 0) {
        return $photoPath;
    } else {
        // Only add the base directory if the path doesn't already contain it
        return $baseDir . $photoPath;
    }
}

/**
 * Sanitize input data
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate NIK format
 * 
 * @param string $nik NIK to validate
 * @return bool True if valid, false otherwise
 */
//function validateNIK($nik) {
// NIK should be 16 digits
//    return preg_match('/^[0-9]{16}$/', $nik);
//}

/**
 * Check if NIK already exists in database
 * 
 * @param string $nik NIK to check
 * @return bool True if exists, false otherwise
 */
function nikExists($nik)
{
    $nik = escapeString($nik);
    $sql = "SELECT nik FROM peserta WHERE nik = '$nik'";
    $result = executeQuery($sql);
    return ($result && $result->num_rows > 0);
}

/**
 * Generate a unique filename for uploads
 * 
 * @param string $originalName Original filename
 * @param string $prefix Optional prefix for the filename
 * @return string Unique filename
 */
function generateUniqueFilename($originalName, $prefix = '')
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return $prefix . '_' . uniqid() . '.' . $extension;
}

/**
 * Format date for display
 * 
 * @param string $date Date in Y-m-d format
 * @return string Formatted date (d-m-Y)
 */
function formatDate($date)
{
    return date('d-m-Y', strtotime($date));
}

/**
 * Calculate age from birth date
 * 
 * @param string $birthDate Birth date in Y-m-d format
 * @return int Age in years
 */
function calculateAge($birthDate)
{
    $today = new DateTime();
    $birth = new DateTime($birthDate);
    $interval = $today->diff($birth);
    return $interval->y;
}

/**
 * Log system activity
 * 
 * @param string $action Action performed
 * @param string $details Additional details
 * @return void
 */
function logActivity($action, $details = '')
{
    // To be implemented
    // Will log activities to a file or database
}

/**
 * Redirect to another page
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null if none exists
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Fetch a single value from database
 * 
 * @param string $query SQL query with placeholders
 * @param array $params Parameters for prepared statement
 * @return mixed|null Single value or null if not found
 */
function fetchValue($query, $params = [])
{
    global $conn;

    try {
        $stmt = $conn->prepare($query);

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_array(MYSQLI_NUM)) {
            return $row[0];
        }

        return null;
    } catch (Exception $e) {
        error_log('Database error in fetchValue: ' . $e->getMessage());
        return null;
    }
}

/**
 * Compress photo upload to target file size while maintaining quality
 * @param string $sourcePath Path to the source image
 * @param int $maxFileSize Maximum file size in bytes (default: 100KB)
 * @param int $minQuality Minimum quality to use (default: 40)
 * @return bool True if compression was successful, false otherwise
 */
function compressPhotoUpload($sourcePath, $maxFileSize = 102400, $minQuality = 40) {
    // Check if file exists and is an image
    if (!file_exists($sourcePath) || !is_file($sourcePath)) {
        error_log("compressPhotoUpload: File does not exist - {$sourcePath}");
        return false;
    }
    
    $fileType = mime_content_type($sourcePath);
    if (strpos($fileType, 'image/') !== 0) {
        error_log("compressPhotoUpload: Not an image file - {$sourcePath}");
        return false;
    }
    
    // Get current file size
    $currentSize = filesize($sourcePath);
    
    // If already smaller than target size, no need to compress
    if ($currentSize <= $maxFileSize) {
        error_log("compressPhotoUpload: File already smaller than target size - {$currentSize} bytes");
        return true;
    }
    
    // Get image information
    list($width, $height, $type) = getimagesize($sourcePath);
    
    // Create image resource based on file type
    $source = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            error_log("compressPhotoUpload: Unsupported image type - {$type}");
            return false;
    }
    
    if (!$source) {
        error_log("compressPhotoUpload: Failed to create image resource");
        return false;
    }
    
    // First approach: Try progressive quality reduction
    if ($type === IMAGETYPE_JPEG) {
        // Try different quality levels
        $quality = 90; // Start with high quality
        $step = 10;    // Reduce by 10% each time
        
        while ($quality >= $minQuality) {
            // Create a temporary file for testing compression
            $tempFile = $sourcePath . '.temp';
            imagejpeg($source, $tempFile, $quality);
            
            $newSize = filesize($tempFile);
            error_log("compressPhotoUpload: Trying quality {$quality}%, size: {$newSize} bytes");
            
            if ($newSize <= $maxFileSize) {
                // Success! Replace original with compressed version
                unlink($sourcePath);
                rename($tempFile, $sourcePath);
                imagedestroy($source);
                error_log("compressPhotoUpload: Successfully compressed to {$newSize} bytes with quality {$quality}%");
                return true;
            }
            
            // Clean up temporary file
            unlink($tempFile);
            
            // Reduce quality and try again
            $quality -= $step;
        }
    }
    
    // Second approach: Resize the image if quality reduction alone didn't work
    // Calculate new dimensions while maintaining aspect ratio
    $maxDimension = 1200; // Start with this max dimension
    
    while ($maxDimension >= 600) { // Don't go below 600px
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = intval($height * ($maxDimension / $width));
        } else {
            $newHeight = $maxDimension;
            $newWidth = intval($width * ($maxDimension / $height));
        }
        
        // Create a new resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG images
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Copy and resize the image
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Try different quality levels with the resized image
        $quality = 80; // Start with lower quality for resized images
        
        while ($quality >= $minQuality) {
            $tempFile = $sourcePath . '.temp';
            
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($resized, $tempFile, $quality);
                    break;
                case IMAGETYPE_PNG:
                    // For PNG, use compression level (0-9)
                    $pngQuality = 9 - floor($quality / 10); // Convert quality to PNG compression (0-9)
                    imagepng($resized, $tempFile, $pngQuality);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($resized, $tempFile);
                    break;
            }
            
            $newSize = filesize($tempFile);
            error_log("compressPhotoUpload: Trying size {$newWidth}x{$newHeight}, quality {$quality}%, size: {$newSize} bytes");
            
            if ($newSize <= $maxFileSize) {
                // Success! Replace original with compressed version
                unlink($sourcePath);
                rename($tempFile, $sourcePath);
                imagedestroy($source);
                imagedestroy($resized);
                error_log("compressPhotoUpload: Successfully compressed to {$newSize} bytes with dimensions {$newWidth}x{$newHeight} and quality {$quality}%");
                return true;
            }
            
            // Clean up temporary file
            unlink($tempFile);
            
            // Reduce quality and try again
            $quality -= 10;
        }
        
        // Free memory
        imagedestroy($resized);
        
        // Reduce size and try again
        $maxDimension -= 200;
    }
    
    // If we got here, we couldn't compress enough
    imagedestroy($source);
    error_log("compressPhotoUpload: Failed to compress image to target size");
    
    // Last resort: Just use the minimum quality and maximum compression
    $resized = imagecreatetruecolor(600, intval(600 * ($height / $width)));
    
    // Create a new source image based on file type
    $lastSource = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $lastSource = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $lastSource = imagecreatefrompng($sourcePath);
            // Preserve transparency
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, 600, intval(600 * ($height / $width)), $transparent);
            break;
        case IMAGETYPE_GIF:
            $lastSource = imagecreatefromgif($sourcePath);
            break;
        default:
            error_log("compressPhotoUpload: Cannot create last resort image");
            return false;
    }
    
    if (!$lastSource) {
        error_log("compressPhotoUpload: Failed to create last resort image resource");
        return false;
    }
    
    imagecopyresampled($resized, $lastSource, 0, 0, 0, 0, 600, intval(600 * ($height / $width)), $width, $height);
    
    // Save with appropriate format and compression
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($resized, $sourcePath, $minQuality);
            break;
        case IMAGETYPE_PNG:
            imagepng($resized, $sourcePath, 9); // Maximum PNG compression
            break;
        case IMAGETYPE_GIF:
            imagegif($resized, $sourcePath);
            break;
    }
    
    imagedestroy($lastSource);
    imagedestroy($resized);
    
    $finalSize = filesize($sourcePath);
    error_log("compressPhotoUpload: Last resort compression resulted in {$finalSize} bytes");
    
    return ($finalSize <= $maxFileSize);
}

/**
 * Upload file to server
 * @param array $file File data from $_FILES
 * @param string $targetDir Target directory (relative to document root)
 * @param string|null $customFilename Custom filename to use instead of generating one
 * @param boolean $optimizeImage Whether to optimize image files (default: true)
 * @return array Result with success status, filename, and error message if any
 */
function uploadFile($file, $targetDir = 'assets/uploads', $customFilename = null, $optimizeImage = true)
{
    // Initialize result array
    $result = [
        'success' => false,
        'filename' => '',
        'error' => ''
    ];

    // Check if file was uploaded properly
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'File upload failed with error code: ' . $file['error'];
        return $result;
    }

    // Optimize image if it's an image file and optimization is enabled
    $isImage = false;
    $tempPath = $file['tmp_name'];
    $fileType = mime_content_type($tempPath);

    // Check if file is an image
    if ($optimizeImage && strpos($fileType, 'image/') === 0) {
        $isImage = true;

        // Check if GD extension is available
        if (extension_loaded('gd')) {
            // Get image information
            list($width, $height, $type) = getimagesize($tempPath);

            // Create image resource based on file type
            $source = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($tempPath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($tempPath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($tempPath);
                    break;
            }

            if ($source) {
                // Resize large images
                $maxDimension = 1200; // Maximum width or height
                $newWidth = $width;
                $newHeight = $height;

                if ($width > $maxDimension || $height > $maxDimension) {
                    if ($width > $height) {
                        $newWidth = $maxDimension;
                        $newHeight = intval($height * ($maxDimension / $width));
                    } else {
                        $newHeight = $maxDimension;
                        $newWidth = intval($width * ($maxDimension / $height));
                    }

                    // Create a new resized image
                    $resized = imagecreatetruecolor($newWidth, $newHeight);

                    // Preserve transparency for PNG images
                    if ($type === IMAGETYPE_PNG) {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                    }

                    // Copy and resize the image
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($source);
                    $source = $resized;

                    // Log resizing operation
                    error_log("Resized image from {$width}x{$height} to {$newWidth}x{$newHeight}");
                }

                // Overwrite the temporary file with the optimized version
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        // Use lower quality for JPEG to reduce file size
                        imagejpeg($source, $tempPath, 80);
                        break;
                    case IMAGETYPE_PNG:
                        // Use higher compression for PNG
                        imagepng($source, $tempPath, 6);
                        break;
                    case IMAGETYPE_GIF:
                        imagegif($source, $tempPath);
                        break;
                }

                imagedestroy($source);

                // Log file size reduction
                $newSize = filesize($tempPath);
                error_log("Optimized image size: {$newSize} bytes");
            }
        }
    }

    // Create target directory if it doesn't exist
    $targetPath = __DIR__ . '/../' . $targetDir;
    if (!is_dir($targetPath)) {
        if (!mkdir($targetPath, 0755, true)) {
            $result['error'] = 'Failed to create upload directory: ' . $targetPath;
            return $result;
        }
    }

    // Pastikan direktori memiliki permission yang benar
    chmod($targetPath, 0755);

    // Use custom filename if provided, otherwise generate unique filename
    if ($customFilename) {
        $filename = $customFilename;
    } else {
        $filename = generateUniqueFilename($file['name']);
    }
    $targetFile = $targetPath . '/' . $filename;

    // Check file type (allow only images)
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    if (!in_array($fileType, $allowedTypes)) {
        $result['error'] = 'Only JPG, JPEG, PNG, GIF, and PDF files are allowed';
        return $result;
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        $result['error'] = 'File is too large (max 5MB)';
        return $result;
    }

    // Try to upload the file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $result['success'] = true;
        $result['filename'] = $targetDir . '/' . $filename;
        
        // Compress image files to be under 200KB if they're photos (but not for OCR)
        if ($isImage && (strpos($targetDir, 'dokumentasi') !== false || strpos($targetDir, 'tanda_anggota') !== false)) {
            // This is a photo upload (not for OCR), so compress it to save space
            $compressionResult = compressPhotoUpload($targetFile);
            if ($compressionResult) {
                error_log("Successfully compressed image: {$targetFile} to under 100KB");
            } else {
                error_log("Warning: Could not compress image: {$targetFile} to target size");
            }
        }
    } else {
        $result['error'] = 'Failed to move uploaded file';
    }

    return $result;
}

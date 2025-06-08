<?php
/**
 * Peserta API
 * 
 * This file handles API requests related to participant data.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/validation.php';
require_once '../includes/ocr.php';

// Set header to JSON
header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        // Get participant data
        if (isset($_GET['nik'])) {
            // Get specific participant by NIK
            $nik = escapeString($_GET['nik']);
            $peserta = fetchRow("SELECT * FROM peserta WHERE nik = '$nik'");
            
            if ($peserta) {
                // Get layanan and additional data for this participant
                $layananList = fetchRows("
                    SELECT l.*, pl.hasil_pemeriksaan, pl.foto_kegiatan, pl.petugas 
                    FROM layanan l
                    JOIN peserta_layanan pl ON l.id = pl.layanan_id
                    WHERE pl.nik = '$nik'
                ");
                
                $peserta['layanan'] = $layananList;
                
                // Add hasil_pemeriksaan and foto_kegiatan from first layanan record if available
                if (!empty($layananList)) {
                    $peserta['hasil_pemeriksaan'] = $layananList[0]['hasil_pemeriksaan'] ?? null;
                    $peserta['foto_kegiatan'] = $layananList[0]['foto_kegiatan'] ?? null;
                }
                echo json_encode(['status' => 'success', 'data' => $peserta]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Peserta tidak ditemukan']);
            }
        } else {
            // Get all participants (with optional pagination)
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $pesertaList = fetchRows("
                SELECT * FROM peserta 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset
            ");
            
            $total = fetchRow("SELECT COUNT(*) as total FROM peserta");
            
            echo json_encode([
                'status' => 'success', 
                'data' => $pesertaList,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total['total'],
                    'pages' => ceil($total['total'] / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        // Create new participant
        // This will be implemented to handle form submissions via AJAX
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate data
        // Process and save to database
        // Return success/error response
        
        echo json_encode(['status' => 'error', 'message' => 'API not yet implemented']);
        break;
        
    case 'PUT':
        // Update participant data
        // This will be implemented to handle updates via AJAX
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate data
        // Process and update database
        // Return success/error response
        
        echo json_encode(['status' => 'error', 'message' => 'API not yet implemented']);
        break;
        
    case 'DELETE':
        // Delete participant
        // This will be implemented to handle deletion via AJAX
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate request
        // Process and delete from database
        // Return success/error response
        
        echo json_encode(['status' => 'error', 'message' => 'API not yet implemented']);
        break;
        
    default:
        // Method not allowed
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

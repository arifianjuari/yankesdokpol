<?php
/**
 * Layanan API
 * 
 * This file handles API requests related to service data.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        // Get all services
        $layananList = fetchRows("SELECT * FROM layanan ORDER BY nama_layanan");
        echo json_encode(['status' => 'success', 'data' => $layananList]);
        break;
        
    case 'POST':
        // Create new service
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate data
        if (empty($data['nama_layanan'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nama layanan tidak boleh kosong']);
            exit;
        }
        
        // Check if service with same name already exists
        $existingLayanan = fetchRow("SELECT id FROM layanan WHERE nama_layanan = ?", [$data['nama_layanan']]);
        if ($existingLayanan) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Layanan dengan nama tersebut sudah ada']);
            exit;
        }
        
        // Insert into database
        $result = executeQuery("INSERT INTO layanan (nama_layanan) VALUES (?)", [$data['nama_layanan']]);
        
        if ($result) {
            $newId = $conn->insert_id;
            echo json_encode([
                'status' => 'success', 
                'message' => 'Layanan berhasil ditambahkan',
                'data' => ['id' => $newId, 'nama_layanan' => $data['nama_layanan']]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan layanan']);
        }
        break;
        
    case 'PUT':
        // Update service
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate data
        if (empty($data['id']) || empty($data['nama_layanan'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID dan nama layanan tidak boleh kosong']);
            exit;
        }
        
        // Check if service exists
        $existingLayanan = fetchRow("SELECT id FROM layanan WHERE id = ?", [$data['id']]);
        if (!$existingLayanan) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Layanan tidak ditemukan']);
            exit;
        }
        
        // Check if name is already used by another service
        $duplicateName = fetchRow("SELECT id FROM layanan WHERE nama_layanan = ? AND id != ?", 
            [$data['nama_layanan'], $data['id']]);
        if ($duplicateName) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Layanan dengan nama tersebut sudah ada']);
            exit;
        }
        
        // Update database
        $result = executeQuery("UPDATE layanan SET nama_layanan = ? WHERE id = ?", 
            [$data['nama_layanan'], $data['id']]);
        
        if ($result) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Layanan berhasil diperbarui',
                'data' => ['id' => $data['id'], 'nama_layanan' => $data['nama_layanan']]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui layanan']);
        }
        break;
        
    case 'DELETE':
        // Delete service
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate request
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID layanan tidak boleh kosong']);
            exit;
        }
        
        // Check if service exists
        $existingLayanan = fetchRow("SELECT id FROM layanan WHERE id = ?", [$data['id']]);
        if (!$existingLayanan) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Layanan tidak ditemukan']);
            exit;
        }
        
        // Check if service is being used by any participant
        $usageCheck = fetchRow("SELECT COUNT(*) as count FROM peserta_layanan WHERE layanan_id = ?", [$data['id']]);
        if ($usageCheck && $usageCheck['count'] > 0) {
            http_response_code(409);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Tidak dapat menghapus layanan karena sedang digunakan oleh peserta'
            ]);
            exit;
        }
        
        // Delete from database
        $result = executeQuery("DELETE FROM layanan WHERE id = ?", [$data['id']]);
        
        if ($result) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Layanan berhasil dihapus'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus layanan']);
        }
        break;
        
    default:
        // Method not allowed
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

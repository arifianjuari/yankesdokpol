<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Gunakan koneksi yang sudah ada dari config/database.php
global $conn;

// Cek login dan role admin
checkLogin();
checkAdminRole();

// Set page title (akan digunakan oleh admin_layout.php)
$pageTitle = 'Manajemen Rayon';

// Gunakan koneksi yang sudah ada
$db = $conn;

// Variabel untuk dikirim ke admin_layout.php
$pageContent = '';
$additionalJs = '';
$dataForView = []; // Untuk melewatkan data ke file konten

/**
 * Menyiapkan data dan konten untuk daftar rayon
 */
function showRayonList($db, &$pageContentFile, &$dataForView, &$additionalJsContent) {
    $rayons = [];
    $query = "SELECT r.*, 
             (SELECT COUNT(*) FROM satker WHERE rayon_id = r.id) as total_satker
             FROM rayon r 
             ORDER BY nama_rayon";
    
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rayons[] = $row;
        }
    } else {
        error_log("Error fetching rayon list: " . $db->error);
        $_SESSION['error'] = 'Gagal mengambil daftar rayon';
    }
    
    $dataForView['rayons'] = $rayons;
    $pageContentFile = 'content/admin_rayon_list.php';
    $additionalJsContent = <<<JS
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.table').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
            },
            "responsive": true,
            "autoWidth": false,
        });
    });
    </script>
JS;
}

/**
 * Mengubah status aktif/tidak aktif rayon
 */
function handleToggleStatus($db, $id) {
    try {
        $stmt = $db->prepare("UPDATE rayon SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error toggling rayon status: " . $e->getMessage());
        $_SESSION['error'] = 'Gagal mengubah status rayon';
    }
    
    header('Location: admin_rayon.php');
    exit;
}

/**
 * Menghapus rayon
 */
function handleRayonDelete($db, $id) {
    $db->begin_transaction();
    
    try {
        // Cek apakah ada satker yang menggunakan rayon ini
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM satker WHERE rayon_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus rayon karena sudah digunakan oleh satker';
        } else {
            $stmt = $db->prepare("DELETE FROM rayon WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $db->commit();
                $_SESSION['success'] = 'Rayon berhasil dihapus';
            } else {
                throw new Exception('Gagal menghapus rayon');
            }
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error deleting rayon: " . $e->getMessage());
        $_SESSION['error'] = 'Terjadi kesalahan saat menghapus rayon';
    }
    
    header('Location: admin_rayon.php');
    exit;
}

/**
 * Menampilkan form tambah/edit rayon
 */
/**
 * Menyiapkan data dan konten untuk form tambah/edit rayon
 */
function handleRayonForm($db, $id, $isNew = false, &$pageContentFile, &$dataForView, &$pageTitleRef) {
    $rayon = ['id' => $id, 'nama_rayon' => '', 'is_active' => 1]; // ID di-pass untuk form action
    $currentAction = $isNew ? 'Tambah' : 'Edit';
    $pageTitleRef = $currentAction . ' Rayon';
    
    if (!$isNew) {
        $stmt = $db->prepare("SELECT * FROM rayon WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rayonData = $result->fetch_assoc();
        
        if (!$rayonData) {
            $_SESSION['error'] = 'Rayon tidak ditemukan';
            header('Location: admin_rayon.php');
            exit;
        }
        $rayon = $rayonData; // Timpa dengan data dari DB
        $pageTitleRef = 'Edit Rayon: ' . htmlspecialchars($rayon['nama_rayon']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama_rayon = trim($_POST['nama_rayon']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($nama_rayon)) {
            $_SESSION['error'] = 'Nama rayon tidak boleh kosong';
        } else {
            $db->begin_transaction();
            
            try {
                if ($isNew) {
                    $stmt = $db->prepare("INSERT INTO rayon (nama_rayon, is_active) VALUES (?, ?)");
                    $stmt->bind_param('si', $nama_rayon, $is_active);
                } else {
                    $stmt = $db->prepare("UPDATE rayon SET nama_rayon = ?, is_active = ? WHERE id = ?");
                    $stmt->bind_param('sii', $nama_rayon, $is_active, $id);
                }
                
                if ($stmt->execute()) {
                    $db->commit();
                    $_SESSION['success'] = $isNew ? 'Rayon berhasil ditambahkan' : 'Rayon berhasil diperbarui';
                    header('Location: admin_rayon.php');
                    exit;
                } else {
                    throw new Exception('Gagal menyimpan data rayon');
                }
            } catch (Exception $e) {
                $db->rollback();
                error_log("Error saving rayon: " . $e->getMessage());
                $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
        
        // Set data untuk form jika terjadi error
        // Jika terjadi error validasi, $rayon sudah berisi data POST
        // Jika tidak ada POST (halaman dimuat pertama kali), $rayon berisi data dari DB atau default
    }
    
    $dataForView['rayon'] = $rayon;
    $dataForView['isNew'] = $isNew; // Untuk logika di form (misal, URL action)
    $pageContentFile = 'content/admin_rayon_form.php';
    // Tidak ada JS tambahan spesifik untuk form ini saat ini
}

// Proses routing aksi
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($action) {
    case 'add':
    case 'edit':
        handleRayonForm($db, $id, ($action === 'add'), $pageContent, $dataForView, $pageTitle);
        break;
    case 'delete':
        handleRayonDelete($db, $id); // Ini melakukan redirect, jadi tidak set $pageContent
        break;
    case 'toggle_status':
        handleToggleStatus($db, $id); // Ini melakukan redirect, jadi tidak set $pageContent
        break;
    default: // 'list'
        showRayonList($db, $pageContent, $dataForView, $additionalJs);
}

// Hanya include layout jika $pageContent telah di-set oleh salah satu handler di atas
// (dan tidak ada redirect yang terjadi sebelumnya)
if (!empty($pageContent)) {
    // Ekstrak variabel dari $dataForView agar bisa diakses di file $pageContent
    // Contoh: $dataForView['rayons'] akan menjadi variabel $rayons
    if (!empty($dataForView)) {
        extract($dataForView);
    }
    include 'includes/admin_layout.php';
} else {
    // Jika $pageContent kosong, kemungkinan karena redirect atau error.
    // Jika ada pesan error di session, pastikan ditampilkan jika belum ada redirect.
    // Namun, fungsi delete dan toggle sudah redirect, jadi ini mungkin tidak perlu.
    // error_log("admin_rayon.php: pageContent is empty, action was {$action}");
}

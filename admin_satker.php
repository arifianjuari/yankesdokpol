<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

global $conn;
checkLogin();
checkAdminRole();

$pageTitle = 'Manajemen Satker';
$db = $conn;
$pageContent = '';
$additionalJs = '';
$dataForView = [];

// Menyiapkan data dan konten untuk daftar satker
function showSatkerList($db, &$pageContentFile, &$dataForView, &$additionalJsContent) {
    $satkers = [];
    $query = "SELECT s.*, r.nama_rayon FROM satker s JOIN rayon r ON s.rayon_id = r.id ORDER BY s.nama_satker";
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $satkers[] = $row;
        }
    } else {
        error_log('Error fetching satker list: ' . $db->error);
        $_SESSION['error'] = 'Gagal mengambil daftar satker';
    }
    $dataForView['satkers'] = $satkers;
    $pageContentFile = 'content/admin_satker_list.php';
    $additionalJsContent = <<<JS
    <script src=\"https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js\"></script>
    <script src=\"https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js\"></script>
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

// Mengubah status aktif/tidak aktif satker
function handleToggleStatus($db, $id) {
    try {
        $stmt = $db->prepare("UPDATE satker SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    } catch (Exception $e) {
        error_log('Error toggling satker status: ' . $e->getMessage());
        $_SESSION['error'] = 'Gagal mengubah status satker';
    }
    header('Location: admin_satker.php');
    exit;
}

// Hapus satker
function handleSatkerDelete($db, $id) {
    try {
        $stmt = $db->prepare("DELETE FROM satker WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['success'] = 'Satker berhasil dihapus.';
    } catch (Exception $e) {
        error_log('Error deleting satker: ' . $e->getMessage());
        $_SESSION['error'] = 'Gagal menghapus satker';
    }
    header('Location: admin_satker.php');
    exit;
}

// Menyiapkan data dan konten untuk form tambah/edit satker
function handleSatkerForm($db, $id, $isNew = false, &$pageContentFile, &$dataForView, &$pageTitleRef) {
    $satker = [
        'id' => '',
        'nama_satker' => '',
        'rayon_id' => '',
        'is_active' => 1
    ];
    if (!$isNew && $id) {
        $stmt = $db->prepare("SELECT * FROM satker WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $satker = $result->fetch_assoc();
        } else {
            $_SESSION['error'] = 'Data satker tidak ditemukan.';
            header('Location: admin_satker.php');
            exit;
        }
    }
    // Ambil daftar rayon untuk dropdown
    $rayons = [];
    $result = $db->query("SELECT id, nama_rayon FROM rayon WHERE is_active = 1 ORDER BY nama_rayon");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rayons[] = $row;
        }
    }
    $dataForView['satker'] = $satker;
    $dataForView['rayons'] = $rayons;
    $pageContentFile = 'content/admin_satker_form.php';
    $pageTitleRef = $isNew ? 'Tambah Satker' : 'Edit Satker';
}

// Simpan tambah/edit satker
function handleSatkerSave($db, $id, $isNew = false) {
    $nama_satker = trim($_POST['nama_satker'] ?? '');
    $rayon_id = intval($_POST['rayon_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    if ($nama_satker === '' || $rayon_id === 0) {
        $_SESSION['error'] = 'Nama satker dan rayon wajib diisi.';
        header('Location: admin_satker.php?action=' . ($isNew ? 'add' : 'edit') . ($id ? '&id=' . $id : ''));
        exit;
    }
    try {
        if ($isNew) {
            $stmt = $db->prepare("INSERT INTO satker (nama_satker, rayon_id, is_active) VALUES (?, ?, ?)");
            $stmt->bind_param('sii', $nama_satker, $rayon_id, $is_active);
            $stmt->execute();
            $_SESSION['success'] = 'Satker berhasil ditambahkan.';
        } else {
            $stmt = $db->prepare("UPDATE satker SET nama_satker=?, rayon_id=?, is_active=? WHERE id=?");
            $stmt->bind_param('siii', $nama_satker, $rayon_id, $is_active, $id);
            $stmt->execute();
            $_SESSION['success'] = 'Satker berhasil diupdate.';
        }
    } catch (Exception $e) {
        error_log('Error saving satker: ' . $e->getMessage());
        $_SESSION['error'] = 'Gagal menyimpan satker. Pastikan nama satker unik.';
    }
    header('Location: admin_satker.php');
    exit;
}

// Routing aksi
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($action) {
    case 'add':
        handleSatkerForm($db, 0, true, $pageContent, $dataForView, $pageTitle);
        break;
    case 'edit':
        handleSatkerForm($db, $id, false, $pageContent, $dataForView, $pageTitle);
        break;
    case 'save':
        handleSatkerSave($db, $id, isset($_POST['is_new']));
        break;
    case 'toggle_status':
        handleToggleStatus($db, $id);
        break;
    case 'delete':
        handleSatkerDelete($db, $id);
        break;
    case 'list':
    default:
        showSatkerList($db, $pageContent, $dataForView, $additionalJs);
        break;
}

// Render layout
require 'includes/admin_layout.php';

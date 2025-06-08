<?php
/**
 * Admin Layout
 * 
 * Layout utama untuk semua halaman admin YankesDokpol
 * Menyediakan struktur HTML konsisten, navigasi admin, dan wrapper konten
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Pastikan sesi sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';

// Jika tidak ada page title, set default
if (!isset($pageTitle)) {
    $pageTitle = 'YankesDokpol';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($additionalCss)): ?>
        <?php echo $additionalCss; ?>
    <?php endif; ?>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#007bff">
    <style>
        /* Fix untuk dropdown admin */
        #adminOffcanvas .dropdown-menu {
            z-index: 1200 !important;
        }
        .main-content-admin {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <?php include 'admin_nav.php'; ?>
        <div class="<?php echo $isAdmin ? 'main-content-admin' : ''; ?>">
            <div class="container py-4">
                <?php if (isset($pageContent)) {
    if (isset($dataForView) && is_array($dataForView)) extract($dataForView);
    include $pageContent;
} ?>
            </div>
        </div>
    <?php else: ?>
        <?php if (isset($pageContent)): ?>
            <?php include $pageContent; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pratinjau Gambar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="modalImagePreview" src="" class="img-fluid" alt="Preview" style="max-height: 80vh; width: auto;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script> <!-- Pastikan Chart.js utama sudah ada -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script>
    // Global function to show image in modal
    function showImagePreview(imageUrl) {
        const modalImage = document.getElementById('modalImagePreview');
        const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        modalImage.src = imageUrl;
        modal.show();
    }
    </script>
    <script src="assets/js/script.js"></script>
    <?php if (isset($additionalJs)): ?>
        <?php echo $additionalJs; ?>
    <?php endif; ?>
</body>
</html>

<?php
// Navigasi admin - dipanggil dari admin_layout.php
// Pastikan $isAdmin sudah didefinisikan di admin_layout.php
if ($isAdmin) :
?>
    <nav class="navbar d-lg-none fixed-top p-0">
        <div class="container-fluid p-0">
            <button class="navbar-toggler ms-auto me-2 mt-2 rounded-circle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminOffcanvas" aria-controls="adminOffcanvas" style="background-color: white; width: 45px; height: 45px; padding: 0.25rem; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <style>
        .nav-link {
            white-space: normal !important;
            text-align: left !important;
            padding: 0.5rem 1rem !important;
        }
        .nav-link i {
            min-width: 20px;
            text-align: center;
            margin-right: 10px;
        }
    </style>
    <div class="offcanvas offcanvas-start bg-dark text-white d-lg-flex flex-column flex-shrink-0 p-3" tabindex="-1" id="adminOffcanvas" aria-labelledby="adminOffcanvasLabel" style="width: 280px;">
        <div class="offcanvas-header d-lg-none p-0">
            <button type="button" class="btn-close btn-close-white text-reset ms-auto" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <div class="logos-container d-flex justify-content-center mb-3">
                <img src="assets/uploads/icon/logo polri copy.png" alt="Logo Polri" class="logo-img mx-1" style="height: 50px; width: auto;">
                <img src="assets/uploads/icon/Biddokkes.png" alt="Logo Biddokkes" class="logo-img mx-1" style="height: 50px; width: auto;">
                <img src="assets/uploads/icon/logo RSHB kecil.png" alt="Logo RSHB" class="logo-img mx-1" style="height: 50px; width: auto;">
            </div>
            <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
                <span class="fs-4" style="color: #cc9900; line-height: 1.2;">HUT Bhayangkara ke-79</span>
            </a>
            <hr class="text-white">
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?> d-flex align-items-start">
                        <i class="bi bi-house-door"></i>
                        <span class="ms-2">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="daftar_peserta.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'daftar_peserta.php' ? 'active' : ''; ?> d-flex align-items-start">
                        <i class="bi bi-card-list"></i>
                        <span class="ms-2">Daftar Peserta</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="identitas_peserta.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'identitas_peserta.php' ? 'active' : ''; ?> d-flex align-items-start">
                        <i class="bi bi-people"></i>
                        <span class="ms-2">Identitas Peserta</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="form_peserta.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'form_peserta.php' ? 'active' : ''; ?> d-flex align-items-start">
                        <i class="bi bi-person-plus"></i>
                        <span class="ms-2">Form Pendaftaran</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="galeri.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'galeri.php' ? 'active' : ''; ?> d-flex align-items-start">
                        <i class="bi bi-images"></i>
                        <span class="ms-2">Galeri Kegiatan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="export.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'export.php' ? 'active' : ''; ?> d-flex align-items-start">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <span class="ms-2">Export Data</span>
                    </a>
                </li>
            </ul>
            <hr class="text-white">
            <div class="dropdown mb-3">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo htmlspecialchars($_SESSION['user_nama_lengkap'] ?? $_SESSION['username']); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="settingsDropdown">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Pengaturan Profil</a></li>
                    <li><a class="dropdown-item" href="event_management.php"><i class="bi bi-calendar-event me-2"></i>Manajemen Acara</a></li>
                    <li><a class="dropdown-item" href="layanan_management.php"><i class="bi bi-card-checklist me-2"></i>Manajemen Layanan</a></li>
                    <li><a class="dropdown-item" href="admin_rayon.php"><i class="bi bi-diagram-3 me-2"></i>Manajemen Rayon</a></li>
<li><a class="dropdown-item" href="admin_satker.php"><i class="bi bi-building me-2"></i>Manajemen Satker</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>
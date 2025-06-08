<?php
/**
 * Galeri Content
 * 
 * Konten halaman galeri, diinclude oleh galeri.php
 */
?>

<style>
/* Gallery Card Styles */
.gallery-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.1);
}

.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.gallery-img-container {
    height: 180px;
    overflow: hidden;
    cursor: pointer;
    background-color: #f8f9fa;
}

.gallery-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.gallery-img-container:hover .gallery-overlay {
    opacity: 1;
}

.gallery-img-container:hover .gallery-img {
    transform: scale(1.1);
}

/* Card content */
.card-title {
    font-size: 0.95rem;
    font-weight: 600;
}

.card-body {
    padding: 1rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .gallery-img-container {
        height: 200px;
    }
}

/* Badge styles */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
}
</style>

<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Galeri Kegiatan</h1>
        </div>
        <div class="card-body">
            <!-- Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Cari</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Nama / hasil pemeriksaan..." 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="acara" class="form-label">Acara</label>
                            <select class="form-select" id="acara" name="acara">
                                <option value="0">Semua Acara</option>
                                <?php foreach ($acaraList as $acara): ?>
                                    <option value="<?php echo $acara['id']; ?>" 
                                        <?php echo ($acaraFilter == $acara['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($acara['nama_acara'] . ' (' . date('d M Y', strtotime($acara['tanggal_mulai'])) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="layanan" class="form-label">Layanan</label>
                            <select class="form-select" id="layanan" name="layanan">
                                <option value="0">Semua Layanan</option>
                                <?php foreach ($layananList as $layanan): ?>
                                    <option value="<?php echo $layanan['id']; ?>" 
                                        <?php echo ($layananFilter == $layanan['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <?php if ($layananFilter > 0 || $acaraFilter > 0 || !empty($searchQuery)): ?>
                                <a href="galeri.php" class="btn btn-outline-secondary ms-2" title="Reset Filter">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Gallery Grid -->
            <?php if (empty($galleryItems)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Tidak ada foto kegiatan yang ditemukan dengan filter yang dipilih.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($galleryItems as $item): 
                        $acaraNama = !empty($item['nama_acara']) ? $item['nama_acara'] : 'Tanpa Acara';
                        $tanggalAcara = !empty($item['tanggal_mulai']) ? date('d M Y', strtotime($item['tanggal_mulai'])) : '';
                        $waktuUpload = date('d M Y H:i', strtotime($item['created_at']));
                    ?>
                        <div class="col">
                            <div class="card h-100 gallery-card">
                                <div class="gallery-img-container position-relative">
                                    <img src="<?php echo htmlspecialchars(getPhotoPath($item['foto_kegiatan'])); ?>" 
                                         class="card-img-top gallery-img" 
                                         alt="Kegiatan <?php echo htmlspecialchars($item['nama']); ?>"
                                         loading="lazy"
                                         style="cursor: pointer;"
                                         onclick="showImagePreview('<?php echo htmlspecialchars(getPhotoPath($item['foto_kegiatan'])); ?>')">
                                    <div class="gallery-overlay d-flex align-items-center justify-content-center">
                                        <button class="btn btn-sm btn-light rounded-pill" onclick="event.stopPropagation(); showImagePreview('<?php echo htmlspecialchars(getPhotoPath($item['foto_kegiatan'])); ?>')">
                                            <i class="bi bi-zoom-in me-1"></i> Perbesar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2" title="<?php echo htmlspecialchars($item['nama']); ?>">
                                        <?php echo htmlspecialchars($item['nama']); ?>
                                    </h6>
                                    <div class="small text-muted mb-2">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi bi-calendar3 me-2"></i>
                                            <span><?php echo $tanggalAcara; ?></span>
                                        </div>
                                        <div class="text-truncate" title="<?php echo htmlspecialchars($acaraNama); ?>">
                                            <i class="bi bi-tag me-2"></i>
                                            <span><?php echo htmlspecialchars($acaraNama); ?></span>
                                        </div>
                                    </div>
                                    <?php if ($item['total_pemeriksaan'] > 1): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-clipboard2-pulse me-2"></i>
                                            <?php echo $item['total_pemeriksaan']; ?> pemeriksaan
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent border-top-0 pt-0 pb-3 px-3">
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i> <?php echo $waktuUpload; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page-1; ?>&layanan=<?php echo $layananFilter; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                                   aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" 
                                       href="?page=<?php echo $i; ?>&layanan=<?php echo $layananFilter; ?>&acara=<?php echo $acaraFilter; ?>&search=<?php echo urlencode($searchQuery); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page+1; ?>&layanan=<?php echo $layananFilter; ?>&acara=<?php echo $acaraFilter; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                                   aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Image Modal -->
<!-- Image preview functionality is now handled in the main layout -->

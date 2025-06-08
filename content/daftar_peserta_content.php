<?php
/**
 * Daftar Peserta Content
 * 
 * Konten halaman daftar peserta untuk ditampilkan dalam layout admin
 */
?>

<!-- Header -->
<header class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Daftar Peserta</h1>
    <a href="form_peserta.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Peserta
    </a>
</header>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Cari berdasarkan NIK, nama, alamat, atau nomor HP..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="acara_id" class="form-select">
                    <option value="">-- Semua Acara --</option>
                    <?php foreach ($acaraList as $id => $nama): ?>
                        <option value="<?php echo $id; ?>" <?php echo (isset($acaraId) && $acaraId == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nama); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($acaraId)): ?>
                    <a href="daftar_peserta.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Peserta Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($pesertaList)): ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-people display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">Tidak ada data peserta</h5>
                <?php if (!empty($search)): ?>
                    <p class="text-muted">Tidak ditemukan hasil pencarian untuk "<?php echo htmlspecialchars($search); ?>"</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Layanan</th>
                            <th>Tanggal Daftar</th>
                            <th>Petugas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesertaList as $peserta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($peserta['nik']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($peserta['nama']); ?>
                                    <div class="small text-muted">
                                        <?php echo !empty($peserta['alamat']) ? htmlspecialchars($peserta['alamat']) : '-'; ?>
                                    </div>
                                </td>
                                <td><?php echo !empty($peserta['nama_layanan']) ? htmlspecialchars($peserta['nama_layanan']) : '-'; ?></td>
                                <td><?php echo !empty($peserta['waktu_pendaftaran']) ? date('d/m/Y H:i', strtotime($peserta['waktu_pendaftaran'])) : '-'; ?></td>
                                <td><?php echo !empty($peserta['petugas']) ? htmlspecialchars($peserta['petugas']) : '-'; ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="form_peserta.php?nik=<?php echo $peserta['nik']; ?>&acara_id=<?php echo $peserta['acara_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="proses_peserta.php?action=delete&id=<?php echo $peserta['peserta_layanan_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pendaftaran ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>">
                                        &laquo;
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>">
                                        &raquo;
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Sukses</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Initialize Toasts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, {autohide: true, delay: 5000});
    });
});
</script>

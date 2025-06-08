<?php
/**
 * Identitas Peserta Content
 * 
 * Konten halaman identitas peserta untuk ditampilkan dalam layout admin
 */
?>

<!-- Header -->
<header class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Identitas Peserta</h1>
    <a href="form_peserta.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Peserta
    </a>
</header>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Cari berdasarkan NIK, nama, alamat, atau nomor HP..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if (!empty($search)): ?>
                    <a href="identitas_peserta.php" class="btn btn-outline-secondary">
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
                            <th>Alamat</th>
                            <th>Tanggal Lahir</th>
                            <th>No. HP</th>
                            <th>Partisipasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesertaList as $peserta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($peserta['nik']); ?></td>
                                <td><?php echo htmlspecialchars($peserta['nama']); ?></td>
                                <td><?php echo !empty($peserta['alamat']) ? htmlspecialchars($peserta['alamat']) : '-'; ?></td>
                                <td><?php echo !empty($peserta['tanggal_lahir']) ? date('d/m/Y', strtotime($peserta['tanggal_lahir'])) : '-'; ?></td>
                                <td><?php echo !empty($peserta['nomor_hp']) ? htmlspecialchars($peserta['nomor_hp']) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $peserta['total_partisipasi'] > 0 ? 'primary' : 'secondary'; ?>">
                                        <?php echo $peserta['total_partisipasi']; ?> kegiatan
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="detail_partisipasi.php?nik=<?php echo $peserta['nik']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Lihat Partisipasi">
                                            <i class="bi bi-calendar-event"></i>
                                        </a>
                                        <a href="form_peserta.php?nik=<?php echo $peserta['nik']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
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

<!-- No modal or JavaScript needed anymore -->

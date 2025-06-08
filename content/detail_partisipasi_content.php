<?php
/**
 * Detail Partisipasi Content
 * 
 * Konten halaman detail partisipasi untuk ditampilkan dalam layout admin
 */
?>

<!-- Header -->
<header class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Daftar Partisipasi - NIK: <?php echo htmlspecialchars($nik); ?></h1>
        <p class="text-muted mb-0">Nama: <?php echo htmlspecialchars($nama); ?></p>
    </div>
    <a href="identitas_peserta.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</header>

<!-- Participations Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($participations)): ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-calendar-x display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">Belum ada riwayat partisipasi</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Acara</th>
                            <th>Layanan</th>
                            <th>Tanggal</th>
                            <th>Petugas</th>
                            <th>Hasil Pemeriksaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($participations as $partisipasi): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($partisipasi['nama_acara'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($partisipasi['nama_layanan'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($partisipasi['tanggal'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($partisipasi['petugas'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($partisipasi['hasil_pemeriksaan'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="showHasil('<?php echo htmlspecialchars(addslashes($partisipasi['hasil_pemeriksaan'])); ?>')">
                                            Lihat Hasil
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <p class="mb-0 text-muted">Total: <?php echo $totalPartisipasi; ?> partisipasi</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Simple Alert for Hasil Pemeriksaan -->
<script>
function showHasil(hasil) {
    alert('Hasil Pemeriksaan:\n\n' + hasil);
}
</script>

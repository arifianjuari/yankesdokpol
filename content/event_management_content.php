<?php

/**
 * Event Management Content
 * 
 * Konten halaman manajemen acara, diinclude oleh layout admin
 */

// Variabel $success dan $message sudah diset di event_management.php
?>

<?php if (isset($success) && $success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($errors['db'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($errors['db']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h2 class="h5 mb-0"><?php echo $editData ? 'Edit Acara' : 'Tambah Acara Baru'; ?></h2>
    </div>
    <div class="card-body">
        <form method="post" action="event_management.php">
            <?php if ($editData): ?>
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($editData['id']); ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="namaEvent" class="form-label">Nama Acara</label>
                        <input type="text" class="form-control <?php echo isset($errors['nama_acara']) ? 'is-invalid' : ''; ?>"
                            id="namaEvent" name="nama_acara" required
                            value="<?php echo $editData ? htmlspecialchars($editData['nama_acara']) : ''; ?>">
                        <?php if (isset($errors['nama_acara'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['nama_acara']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input type="text" class="form-control <?php echo isset($errors['lokasi']) ? 'is-invalid' : ''; ?>"
                            id="lokasi" name="lokasi" required
                            value="<?php echo $editData ? htmlspecialchars($editData['lokasi']) : ''; ?>">
                        <?php if (isset($errors['lokasi'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['lokasi']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggalMulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control <?php echo isset($errors['tanggal_mulai']) ? 'is-invalid' : ''; ?>"
                            id="tanggalMulai" name="tanggal_mulai" required
                            value="<?php echo $editData ? htmlspecialchars($editData['tanggal_mulai']) : ''; ?>">
                        <?php if (isset($errors['tanggal_mulai'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['tanggal_mulai']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggalSelesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control <?php echo isset($errors['tanggal_selesai']) ? 'is-invalid' : ''; ?>"
                            id="tanggalSelesai" name="tanggal_selesai" required
                            value="<?php echo $editData ? htmlspecialchars($editData['tanggal_selesai']) : ''; ?>">
                        <?php if (isset($errors['tanggal_selesai'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['tanggal_selesai']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo $editData ? htmlspecialchars($editData['deskripsi']) : ''; ?></textarea>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="isActive" name="status"
                    <?php echo (!$editData || $editData['status'] === 'aktif') ? 'checked' : ''; ?>>
                <label class="form-check-label" for="isActive">Acara Aktif</label>
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editData ? 'Update Acara' : 'Tambah Acara'; ?>
                </button>
                <?php if ($editData): ?>
                    <a href="event_management.php" class="btn btn-secondary">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h2 class="h5 mb-0">Daftar Acara</h2>
    </div>
    <div class="card-body">
        <?php if (empty($acaraList)): ?>
            <div class="alert alert-info">Belum ada data acara.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nama Acara</th>
                            <th>Lokasi</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($acaraList as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['nama_acara']); ?></td>
                                <td><?php echo htmlspecialchars($event['lokasi']); ?></td>
                                <td>
                                    <?php
                                    echo date('d/m/Y', strtotime($event['tanggal_mulai']));
                                    if ($event['tanggal_mulai'] !== $event['tanggal_selesai']) {
                                        echo ' - ' . date('d/m/Y', strtotime($event['tanggal_selesai']));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $event['status'] === 'aktif' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $event['status'] === 'aktif' ? 'Aktif' : 'Selesai'; ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <a href="event_management.php?action=edit&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="event_management.php?action=toggle_status&id=<?php echo $event['id']; ?>"
                                        class="btn btn-sm <?php echo $event['status'] === 'aktif' ? 'btn-warning' : 'btn-success'; ?>"
                                        onclick="return confirm('Apakah Anda yakin ingin <?php echo $event['status'] === 'aktif' ? 'menonaktifkan' : 'mengaktifkan'; ?> acara ini?')">
                                        <i class="bi <?php echo $event['status'] === 'aktif' ? 'bi-pause-circle' : 'bi-play-circle'; ?>"></i>
                                        <?php echo $event['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                    </a>
                                    <a href="event_management.php?action=delete&id=<?php echo $event['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus acara ini?\\n\\nTindakan ini akan menghapus acara secara permanen.\\n\\nAcara yang sudah memiliki data peserta tidak dapat dihapus.')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
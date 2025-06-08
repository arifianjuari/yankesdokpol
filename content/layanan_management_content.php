<?php
/**
 * Layanan Management Content
 * 
 * Konten halaman manajemen layanan, diinclude oleh layout admin
 */
?>

<div class="container py-4">
    <?php if (isset($message) && $message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo isset($editData) ? 'Edit' : 'Tambah'; ?> Layanan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php if (isset($editData)): ?>
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="nama_layanan" class="form-label">Nama Layanan</label>
                    <input type="text" class="form-control" id="nama_layanan" name="nama_layanan" 
                           value="<?php echo isset($editData) ? htmlspecialchars($editData['nama_layanan']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php 
                        echo isset($editData) ? htmlspecialchars($editData['deskripsi']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="status" name="status" 
                           <?php echo (isset($editData) && $editData['status'] == 1) || !isset($editData) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="status">Aktif</label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <?php if (isset($editData)): ?>
                        <a href="layanan_management.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Layanan -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Daftar Layanan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nama Layanan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($layananList)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Tidak ada data layanan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($layananList as $layanan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($layanan['nama_layanan']); ?></td>
                                    <td>
                                        <?php 
                                        $status = $layanan['status'] ?? 0;
                                        $statusText = $status ? 'Aktif' : 'Nonaktif';
                                        $statusClass = $status ? 'success' : 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $layanan['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $layanan['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus layanan <?php echo addslashes($layanan['nama_layanan']); ?>?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $pageTitle ?></h1>
        <a href="admin_rayon.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Rayon</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="form-group">
                    <label for="nama_rayon">Nama Rayon <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($_SESSION['error']) ? 'is-invalid' : '' ?>" 
                           id="nama_rayon" name="nama_rayon" 
                           value="<?= htmlspecialchars($rayon['nama_rayon']) ?>" required>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="invalid-feedback">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_active" 
                               name="is_active" value="1" <?= $rayon['is_active'] ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="is_active">Aktif</label>
                    </div>
                    <small class="form-text text-muted">
                        Nonaktifkan untuk menyembunyikan rayon dari daftar pilihan
                    </small>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="admin_rayon.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

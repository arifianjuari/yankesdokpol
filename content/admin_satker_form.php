<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?= $pageTitle ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post" action="admin_satker.php?action=save<?= isset($satker['id']) && $satker['id'] ? '&id=' . $satker['id'] : '' ?>">
                        <input type="hidden" name="is_new" value="<?= empty($satker['id']) ? '1' : '0' ?>">
                        <div class="form-group mb-3">
                            <label for="nama_satker">Nama Satker <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_satker" name="nama_satker" value="<?= htmlspecialchars($satker['nama_satker'] ?? '') ?>" required maxlength="100">
                        </div>
                        <div class="form-group mb-3">
                            <label for="rayon_id">Rayon <span class="text-danger">*</span></label>
                            <select class="form-control" id="rayon_id" name="rayon_id" required>
                                <option value="">Pilih Rayon</option>
                                <?php foreach ($rayons as $rayon): ?>
                                    <option value="<?= $rayon['id'] ?>" <?= ($satker['rayon_id'] ?? '') == $rayon['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rayon['nama_rayon']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Status</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ($satker['is_active'] ?? 1) ? 'checked' : '' ?> />
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success">Simpan</button>
                            <a href="admin_satker.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

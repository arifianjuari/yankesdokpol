<?php
/**
 * Form Peserta Content
 * 
 * Konten halaman form pendaftaran/edit peserta untuk ditampilkan dalam layout admin
 */
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h2 class="h5 mb-0"><?php echo $editMode ? 'Edit Data Peserta' : 'Form Pendaftaran Peserta'; ?></h2>
    </div>
    <div class="card-body">
        <form id="registrationForm" action="form_peserta.php" method="post" enctype="multipart/form-data">
            <?php if ($editMode): ?>
                <input type="hidden" name="update_existing" value="1">
            <?php endif; ?>

            <!-- Pilih Acara -->
            <div class="mb-3">
                <label for="acara_id" class="form-label">Pilih Acara <span class="text-danger">*</span></label>
                <select class="form-select <?php echo isset($errors['acara_id']) ? 'is-invalid' : ''; ?>" id="acara_id" name="acara_id" required>
                    <option value="">-- Pilih Acara --</option>
                    <?php foreach ($acaraList as $acara): ?>
                        <option value="<?php echo $acara['id']; ?>" <?php echo ($editMode && $existingData['acara_id'] == $acara['id']) || (isset($_POST['acara_id']) && $_POST['acara_id'] == $acara['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($acara['nama_acara']); ?> - <?php echo date('d/m/Y', strtotime($acara['tanggal'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['acara_id'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['acara_id']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nik" class="form-label">NIK</label>
                        <input type="text" class="form-control <?php echo isset($errors['nik']) ? 'is-invalid' : ''; ?>" id="nik" name="nik" maxlength="16" value="<?php echo $editMode ? htmlspecialchars($existingData['nik']) : (isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''); ?>" <?php echo $editMode ? 'readonly' : ''; ?>>
                        <?php if (isset($errors['nik'])): ?>
                            <div class="invalid-feedback">NIK <?php echo $errors['nik']; ?></div>
                        <?php endif; ?>
                        <small class="text-muted">NIK akan otomatis dibuat jika dikosongkan</small>
                    </div>

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" id="nama" name="nama" required value="<?php echo $editMode ? htmlspecialchars($existingData['nama']) : (isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''); ?>">
                        <?php if (isset($errors['nama'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['nama']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea class="form-control <?php echo isset($errors['alamat']) ? 'is-invalid' : ''; ?>" id="alamat" name="alamat" rows="3" required><?php echo $editMode ? htmlspecialchars($existingData['alamat']) : (isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''); ?></textarea>
                        <?php if (isset($errors['alamat'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['alamat']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?php echo isset($errors['tanggal_lahir']) ? 'is-invalid' : ''; ?>" id="tanggal_lahir" name="tanggal_lahir" required value="<?php echo $editMode ? htmlspecialchars($existingData['tanggal_lahir']) : (isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : ''); ?>">
                        <?php if (isset($errors['tanggal_lahir'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['tanggal_lahir']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="nomor_hp" class="form-label">Nomor HP</label>
                        <input type="text" class="form-control" id="nomor_hp" name="nomor_hp" value="<?php echo $editMode ? htmlspecialchars($existingData['nomor_hp']) : (isset($_POST['nomor_hp']) ? htmlspecialchars($_POST['nomor_hp']) : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="petugas" class="form-label">Petugas</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="petugasInput" list="petugasList" placeholder="Masukkan nama petugas">
                            <button class="btn btn-secondary" type="button" id="addPetugasBtn">Tambah</button>
                        </div>
                        <datalist id="petugasList"></datalist>
                        <div class="mt-2" id="selectedPetugasContainer">
                            <input type="hidden" name="petugas" id="petugasField" value="<?php echo $editMode && isset($existingData['petugas']) ? htmlspecialchars($existingData['petugas']) : (isset($_POST['petugas']) ? htmlspecialchars($_POST['petugas']) : ''); ?>">
                            <?php if ($editMode && isset($existingData['petugas']) && $existingData['petugas']): ?>
                                <span class="badge bg-primary mb-1 selected-petugas">
                                    <?php echo htmlspecialchars($existingData['petugas']); ?>
                                    <i class="bi bi-x selected-petugas-remove"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Pilih atau tambahkan nama petugas yang melayani</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="row">
                    <?php foreach ($layananList as $layanan): ?>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="layanan[]" id="layanan<?php echo $layanan['id']; ?>" value="<?php echo $layanan['id']; ?>"
                                <?php 
                                    if ($editMode && in_array($layanan['id'], $existingLayanan)) {
                                        echo 'checked';
                                    } elseif (isset($_POST['layanan']) && in_array($layanan['id'], $_POST['layanan'])) {
                                        echo 'checked';
                                    }
                                ?>>
                                <label class="form-check-label" for="layanan<?php echo $layanan['id']; ?>">
                                    <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['layanan'])): ?>
                    <div class="text-danger mt-2"><?php echo $errors['layanan']; ?></div>
                <?php endif; ?>
            </div>

            <!-- File Uploads -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="file_ktp" class="form-label">Upload KTP</label>
                        <input type="file" class="form-control" id="file_ktp" name="file_ktp" accept="image/*">
                        <?php if ($editMode && !empty($existingData['file_ktp'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($existingData['file_ktp']); ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Lihat KTP
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($errors['file_ktp'])): ?>
                            <div class="text-danger"><?php echo $errors['file_ktp']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="file_tanda_anggota" class="form-label">Upload Tanda Anggota (Opsional)</label>
                        <input type="file" class="form-control" id="file_tanda_anggota" name="file_tanda_anggota" accept="image/*">
                        <?php if ($editMode && !empty($existingData['file_tanda_anggota'])): ?>
                            <div class="mt-2">
                                <a href="assets/uploads/tanda_anggota/<?php echo htmlspecialchars($existingData['file_tanda_anggota']); ?>" target="_blank" class="btn btn-sm btn-info mb-1">
                                    <i class="bi bi-eye"></i> Lihat Tanda Anggota
                                </a>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hapus_file_tanda_anggota" name="hapus_file_tanda_anggota" value="1">
                                    <label class="form-check-label small text-danger" for="hapus_file_tanda_anggota">
                                        Hapus file ini
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($errors['file_tanda_anggota'])): ?>
                            <div class="text-danger"><?php echo $errors['file_tanda_anggota']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="foto_kegiatan" class="form-label">Upload Foto Kegiatan</label>
                        <input type="file" class="form-control" id="foto_kegiatan" name="foto_kegiatan" accept="image/*">
                        <?php if ($editMode && !empty($existingData['foto_kegiatan'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars(getPhotoPath($existingData['foto_kegiatan'])); ?>" target="_blank" class="btn btn-sm btn-info mb-1">
                                    <i class="bi bi-eye"></i> Lihat Foto
                                </a>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hapus_foto_kegiatan" name="hapus_foto_kegiatan" value="1">
                                    <label class="form-check-label small text-danger" for="hapus_foto_kegiatan">
                                        Hapus foto ini
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($errors['foto_kegiatan'])): ?>
                            <div class="text-danger"><?php echo $errors['foto_kegiatan']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="hasil_pemeriksaan" class="form-label">Hasil Pemeriksaan</label>
                <textarea class="form-control" id="hasil_pemeriksaan" name="hasil_pemeriksaan" rows="3"><?php echo $editMode && isset($existingData['hasil_pemeriksaan']) ? htmlspecialchars($existingData['hasil_pemeriksaan']) : (isset($_POST['hasil_pemeriksaan']) ? htmlspecialchars($_POST['hasil_pemeriksaan']) : ''); ?></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="daftar_peserta.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-save"></i> <?php echo $editMode ? 'Update' : 'Simpan'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="mt-4">
    <?php if (!empty($errors['db'])): ?>
        <div class="alert alert-danger">
            <?php echo $errors['db']; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <div class="alert alert-success">
            Data peserta berhasil disimpan!
        </div>
    <?php endif; ?>
</div>

<!-- Debug info, hanya tampil jika diperlukan -->
<?php if (isset($debug) && !empty($debug)): ?>
    <div class="card mt-4">
        <div class="card-header">Debug Info</div>
        <div class="card-body">
            <pre><?php print_r($debug); ?></pre>
        </div>
    </div>
<?php endif; ?>
